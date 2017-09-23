<?php

namespace Zoor\GridModel;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class GridModel
{
    public static function table($data){
        $time_start = microtime(true);
        $user = Auth::user();
        $data['show_time_exec'] = true;
        $request = Request::capture();
        $action = $data['prefix_url_action'];
        $query = $data['query'];
        $table = $query->getModel()->getTable();
        $prefixCol = $query->getModel()->prefix_col;
        $modelName = get_class($query->getModel());

        //dd($query);
        $defaultOrder = [];// ['column'=>$query->getModel()->prefix_col.'created_at', 'direction'=>'desc'];
        if( $request->get('orderby') ){
            $defaultOrder = ['column'=>$request->get('orderby'), 'direction'=>$request->get('order') ? $request->get('order') : $defaultOrder['direction']];
        }

        if( $request->get('filters') ){
            foreach($request->get('filters') as $k => $v ){
                if( $v )
                    $query->where($k, $v);
            }
        }

        if( $request->get('filter_created_at') && $prefixCol ){
            $d = \Carbon\Carbon::createFromTimestamp(strtotime($request->get('filter_created_at')));
            $query->whereDate($prefixCol.'created_at', '=', $d);
        }

        if( ($author = $request->get('author')) && $prefixCol ){
            $query->where($prefixCol.'created_by', $request->get('author'));
        }

        if( $s = $request->get('s') ){
            //dd($data['coloumns_search']);
            $query->where(function ($query) use ($data, $s) {
                foreach($data['coloumns_search'] as $k => $v ){
                    if( strpos($v, 'like') > -1 ){
                        $s = '%'.$s.'%';
                    }
                    $query->where($k, $v, $s, 'OR');
                }
            });
        }

        if( isset($data['advanced_search']) && $data['advanced_search'] ) {
            if ( $request->get('as') ) {
                foreach ($request->get('as') as $k => $v) {
                    if ($v) {
                        foreach ($data['advanced_search'] as $asKey => $field) {
                            if( $k == $asKey ) {
                                $query->where(function ($query) use ($v, $field) {
                                    foreach ($field['fields'] as $field) {
                                        $query->where($field, '=', $v, 'OR');
                                    }
                                });
                            }
                        }

                        /*if ($k == 'product_category') {
                            $query->where(function ($query) use ($v) {
                                $query->orWhere('product__cat_id_lvl_1', '=', $v, 'OR')
                                    ->where('product__cat_id_lvl_2', '=', $v, 'OR')
                                    ->where('product__cat_id_lvl_3', '=', $v, 'OR');
                            });
                        } else {
                            $query->where($k, $v);
                        }*/
                    }
                }
            }
        }

        if( $defaultOrder )
            $query->orderBy($defaultOrder['column'],$defaultOrder['direction']);

        //dd($query->toSql());
        $perpage = $request->get('perpage') ? $request->get('perpage') : 10;

        $models = $query->paginate( $perpage );//->toArray();
        //dd($models);
        $totalRow = $models->total();

        //dd($models);
        $requestAll = $request->all();
        $totalCol = count($data['coloumns']) + 2;

        $start = ($models->perPage()*($models->currentPage()-1)+1);
        $end = count($models)+($start-1);

        ?>
        <div class="form-inline">
            <div class="row">
                <div class="col-md-2">
                    <form class="form-search-advanced">
                        <input type="text" name="s" value="<?= $request->get('s') ?>" class="form-control"  placeholder="<?= isset($data['coloumns_search_placeholder']) ? $data['coloumns_search_placeholder'] : 'Search' ?>" >
                        <!--<button class="btn btn-primary form-inline">Go !</button>-->
                        <?php echo self::renderHiddenInput($requestAll, null, 's'); ?>
                    </form>
                </div>
                <?php if( isset($data['filter_created_at']) ) : ?>
                    <div class="col-md-2">
                        <form class="form-search-advanced-created-at">
                            <input type="text" name="filter_created_at" value="<?= $request->get('filter_created_at') ?>" class="form-control grid-filter datepicker" placeholder="Filter by Date">
                            <?php echo self::renderHiddenInput($requestAll, null, 'filter_created_at'); ?>
                        </form>
                    </div>
                <?php endif; ?>
                <?php
                if( isset($data['advanced_search']) && $data['advanced_search'] ) :
                    foreach( $data['advanced_search'] as $advanceSearchKey => $advanceSearch ) : //dd($advanceSearch['options']['class'])?>
                        <div class="col-md-3">
                            <form class="form-search-advanced">
                                <select name="as[<?=$advanceSearchKey?>]"
                                    <?php if( isset($advanceSearch['options']['data']) ) :
                                        foreach($advanceSearch['options']['data'] as $opDataKey => $opData) :
                                            echo 'data-'.$opDataKey.'="'.$opData.'"';
                                        endforeach;
                                    endif;
                                    ?>
                                        class="grid-filter form-control <?= isset($advanceSearch['options']['class']) ? $advanceSearch['options']['class'] : '' ?>">
                                    <option value=""><?= isset($advanceSearch['options']['data']['title']) ? $advanceSearch['options']['data']['title'] : trans('admin.select_an_option')  ?></option>
                                    <?php foreach($advanceSearch['list_dropdown'] as $opKey => $opVal ) :
                                        $selected = '';
                                        if( $request->get('as') && isset($request->get('as')[$advanceSearchKey])
                                            && $request->get('as')[$advanceSearchKey] == $opKey
                                        ){
                                            $selected = 'selected="selected"';
                                        }
                                        ?>
                                        <option value="<?= $opKey ?>" <?=$selected?>><?= $opVal ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php
                                $coloumn = [];
                                $coloumn['filter_field'] = $advanceSearchKey;
                                echo self::renderHiddenInput($requestAll, $coloumn, null);
                                ?>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="separate-half"></div>
        <?php

        ?>

        <div class="row">
            <div class="col-xs-4">

                <form class="bulk_grid_form form-inline" action="<?= '';//route('gridmodel.bulk-action') ?>" method="post">
                    <select name="bulk_grid" class="form-control bulk-grid">
                        <option value=""><?= trans('admin.select') ?></option>
                        <option value="delete"><?= trans('admin.delete') ?></option>
                        <?php if(isset($data['bulk_status'])) : ?>
                            <?php foreach($data['bulk_status'] as $stK => $stV) : ?>
                                <option value="set_status-<?=$stK?>"><?= trans('admin.mark_as') ?> <?=$stV?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button class="btn btn-default">Go!</button>
                    <?= csrf_field() ;?>
                    <input type="hidden" name="bulk_grid_table" value="<?= $table ?>" >
                    <input type="hidden" name="bulk_grid_model" value="<?= $modelName ?>" >
                    <input type="hidden" name="bulk_grid_redirect" value="<?= $request->fullUrl() ?>" >

                    <input type="hidden" name="bulk_grid_ids" class="bulk_grid_ids" value="" >
                </form>

            </div>

            <div class="col-xs-4">
                Showing <?= $start ?> - <?=  $end ?> from <?= number_format($totalRow,0,',','.') ?>.
            </div>

            <div class="col-xs-4">
                <div class="pull-right">
                    <form class="form-inline">
                        perpage <?php
                        $perpages = [10,20,50,100];
                        foreach( $perpages as $ppage ):

                            ?>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="perpage" id="perpage-<?=$ppage?>" value="<?=$ppage?>" class="form-control grid-filter"
                                        <?= $ppage==$perpage ? 'checked="checked"' : '' ?>
                                    ><?=$ppage?>
                                    <span class="cr"><i class="cr-icon fa fa-star"></i></span>
                                </label>
                            </div>

                        <?php endforeach; ?>
                        <!--<button class="btn btn-primary form-inline">Go !</button>-->
                        <?php echo self::renderHiddenInput($requestAll, null, 'perpage'); ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="separate-half"></div>
        <table class="table table-bordered table-striped table-condensed cf">
            <thead class="cf">
            <tr>
                <!--<td>#</td>-->
                <td>
                    <div class="checkbox">
                        <label style="font-size: 1.25em">
                            <input type="checkbox" name="grid_all_ids" class="toggle-grid-ids" autocomplete="off" >
                            <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                        </label>
                    </div>
                </td>
                <?php
                $j = 0;
                foreach( $data['coloumns'] as $colKey => $coloumn ) : ?>
                    <th class="<?= $j < 1 ? 'm' : 'd' ?>"><?php
                        if( isset($coloumn['filter_dropdown']) ) {
                            $html = '<form>';
                        } else {
                            $html = '';
                        }

                        if( isset($coloumn['filter_dropdown']) ){
                            $filter_dropdown = '<select name="filters['.$coloumn['filter_field'].']" class="grid-filter form-control"><option value="">'.trans('admin.select').' '.$coloumn['title'].' </option>';
                            foreach($coloumn['filter_dropdown'] as $filterKey => $filterTitle){
                                $selected = '';
                                if( $request->get('filters') && isset($request->get('filters')[$coloumn['filter_field']])
                                    && $request->get('filters')[$coloumn['filter_field']] == $filterKey
                                ){
                                    $selected = 'selected="selected"';
                                }
                                $filter_dropdown .= '<option value="'.$filterKey.'" '.$selected.'>'.$filterTitle.'</option>';
                            }

                            $filter_dropdown .= '</select>';

                            $html .= $filter_dropdown;
                        } else {
                            if( isset($coloumn['sort']) && $coloumn['sort'] ) {
                                $header = $coloumn['title'];
                                $currUrl = Request::fullUrlWithQuery(['orderby'=>$colKey, 'order'=>'asc']);
                                if( $request->get('orderby') == $colKey ) {
                                    if ( $request->get('order') == 'asc' ){
                                        $header .= " <i class='fa fa-caret-down pull-right'></i>";
                                        $currUrl = Request::fullUrlWithQuery(['page'=>1,'orderby' => $colKey, 'order' => 'desc']);
                                    } else {
                                        $header .= " <i class='fa fa-caret-up pull-right'></i>";
                                        $currUrl = Request::fullUrlWithQuery(['page'=>1,'orderby'=>$colKey, 'order'=>'asc']);
                                    }
                                }
                                $html .= '<a href="'.$currUrl.'">'.$header.'</a>';

                            } else {
                                $html .= $coloumn['title'];
                            }
                        }

                        if( isset($coloumn['filter_dropdown']) ) {
                            $html .= self::renderHiddenInput($requestAll, $coloumn, $colKey);
                            $html .= '</form>';
                        }
                        echo $html;

                        ?></th>
                    <?php $j++; endforeach; ?>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if( $totalRow ) : ?>
                <?php $i = ($models->perPage()*($models->currentPage()-1)+1); ?>
                <?php foreach($models as $model ) : ?>
                    <?php
                    $id = $model->getKey();
                    $canUpdate = \Gate::allows($action.'.update', $model);
                    $canDelete = \Gate::allows($action.'.delete', $model);
                    ?>
                    <tr>
                        <!--<td><?/*= $i */?></td>-->
                        <?php /*if($canEdit||$canDelete) : */?>
                            <td>
                                <div class="checkbox">
                                    <label style="font-size: 1em">
                                        <input class="grid_id" type="checkbox" name="grid_ids[]" value="<?=$id?>" autocomplete="off" >
                                        <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                                    </label>
                                </div>
                            </td>
                        <?php /*endif; */?>
                        <?php $j = 0; foreach( $data['coloumns'] as $colKey => $coloumn ) :
                            $isImgLazier = isset($coloumn['img_lazy_load']) && $coloumn['img_lazy_load'] ? $coloumn['img_lazy_load'] : '';
                            $isTermLazier = isset($coloumn['term_lazy_load']) && $coloumn['term_lazy_load'] ? $coloumn['term_lazy_load'] : '';
                            $isAuthorLazier = isset($coloumn['author_lazy_load']) && $coloumn['author_lazy_load'] ? $coloumn['author_lazy_load'] : '';

                            ?>
                            <td

                                <?= $isTermLazier ? 'data-term-route="'.$isTermLazier['route'].'"' : '' ?>
                                <?= $isImgLazier ? 'data-img-route="'.$isImgLazier['route'].'"' : '' ?>
                                <?= $isAuthorLazier ? 'data-author-route="'.$isAuthorLazier['route'].'"' : '' ?>
                                <?= $isAuthorLazier ? 'data-curr-route="'.$isAuthorLazier['curr-route'].'"' : '' ?>

                                data-id="<?= htmlentities($model->$colKey) ?>" data-pk="<?= $id ?>" class="<?= $j < 1 ? 'm' : 'd' ?>
                                <?= $isImgLazier && $isImgLazier ? $isImgLazier['class'] : '' ?>
                                <?= $isTermLazier && $isTermLazier ? $isTermLazier['class'] : '' ?>
                                <?= $isAuthorLazier && $isAuthorLazier ? $isAuthorLazier['class'] : '' ?>"><?php
                                if( !$isImgLazier ) {
                                    if (isset($coloumn['callback_row'])) {
                                        echo call_user_func($coloumn['callback_row'], $model);
                                    } elseif (isset($coloumn['isDesc'])) {
                                        echo substr($model->$colKey, 0, 30);
                                    } else {
                                        echo $model->$colKey;
                                    }
                                }
                                ?></td>
                            <?php  $j++; endforeach; ?>
                        <td>
                            <?php if($canUpdate): ?>
                                <a href="<?= url("admin/$action/$id/edit") ?>" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a>
                            <?php endif; ?>
                            <?php if($canDelete): ?>
                                <a href="<?= url("admin/$action/$id/delete") ?>" class="btn btn-danger btn-xs alert-delete"><i class="fa fa-trash-o "></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $totalCol ?>"><strong>Not Found</strong></td>
                </tr>
            <?php endif; ?>
            </tbody>
            <!--<tfoot>
            <tr>
                <td colspan="<?/*= $totalCol */?>"><strong>Total Row: <?/*= $totalRow */?></strong></td>
            </tr>
            </tfoot>-->
        </table>
        <?= $models->appends($requestAll)->links(); ?>
        <?php
        if($data['show_time_exec']){
            $time_end = microtime(true);
            $exec_time = ($time_end-$time_start);
            echo '<p>Total Render: <span class="label label-info">'.($exec_time).'</span> Second</p>';
        }
    }

    public static function renderHiddenInput($requestAll, $coloumn=null, $colKey=null){
        $html = '';
        unset($requestAll['page']);
        foreach($requestAll as $k => $v){

            if( is_array($v) ){

                foreach($v as $vk => $vv){
                    //dd($vk, $coloumn['filter_field']);
                    if( $coloumn && isset($coloumn['filter_field']) ){
                        if( is_array($coloumn['filter_field']) ){
                            foreach( $coloumn['filter_field'] as $colField ){
                                if( $vk == $colField ) continue;
                            }
                        } else {
                            if ( $vk == $coloumn['filter_field'] ) continue;
                        }
                    }

                    $html .= '<input class="form-control" type="hidden" value="'.$vv.'" name="'.$k.'['.$vk.']">';
                }
            } else {
                if( $colKey && $k == $colKey ) continue;
                $html .= '<input class="form-control" type="hidden" value="'.$v.'" name="'.$k.'">';
            }
        }
        $html .= '<input class="form-control"  type="hidden" value="1" name="page">';

        return $html;
    }

}
