<?php

namespace Zoor\GridModel;

use App\Models\Entities\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Request;
use Illuminate\Support\Facades\Route;
use App\Models\Entities\Library;
use Session;
use App\Http\Controllers\Controller;

class GridModelController extends Controller
{
    public function __construct(){}

    public function doBulk(){
        $req = Request::capture();
        if( $bkg = $req->get('bulk_grid') ) {
            $model = $req->get('bulk_grid_model');
            $table = with(new $model)->getTable();
            $pk = with(new $model)->getKeyName();
            $prefixCol = with(new $model)->prefix_col;
            $tblPrefix = \DB::getTablePrefix();
            if ($req->get('bulk_grid_ids') && ($ids = $req->get('bulk_grid_ids'))) {
                if ($bkg == 'delete') {
                    $model::destroy(explode(",",$ids));
                    Session::flash($req->get('bulk_grid_table'), 'Success Deleted');
                } elseif (strpos($bkg, 'set_status') > -1) {
                    $listStatus = $model::listStatus();
                    //dd($ids);
                    foreach ($listStatus as $stK => $stV) {
                        if ($bkg == "set_status-" . $stK) {
                            \DB::update("UPDATE " . $tblPrefix . $table . " SET " . $prefixCol . "status=:s WHERE " . $pk . " IN (".$ids.") ", ['s' => $stK]);
                            Session::flash($req->get('bulk_grid_table'), "Success " . trans("admin.mark_as") . " " . $stV);
                        }
                    }

                }
                //$model::whereIn('id', $req->get('bulk_grid_ids'))->all();
            }

            return redirect($req->get('bulk_grid_redirect'));
        }
    }
}