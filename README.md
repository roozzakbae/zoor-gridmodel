# zoor-gridmodel

Laravel 5.4, Grid, Grid Model, Grid Table, Populate Master Data, Bootstrap Css

### Feature
- filter perpage
- filter column with dropdown
- search by column you defined
- bulk update
- bulk delete

install via composer

```sh
composer require zoor/gridmodel
```

insert this line into array providers on config/app.php

```sh
Zoor\GridModel\GridModelServiceProvider::class,
```

 # Usage
 controller
 ```sh
 public function index(){
    $query = CategoryPost::query();
    $sqlOpLike = 'like'; // if your database is postgresql you should change to 'ilike'
    $listStatus = [1=> 'Active', 2=> 'Inactive'];
    $listAncestor = CategoryPost::where('cat_post__parent_id', NULL)->pluck('cat_post_name','cat_post_id')->toArray();
    return view('admin.category-post.index', compact('query', 'sqlOpLike', 'listStatus', 'listAncestor'));
 }
 
 ```
 
views template
```sh

Zoor\GridModel\GridModel::table([
        'prefix_url_action' => 'category-post',
        'query' => $query,
        'bulk_status' => $listStatus,
        'coloumns_search' => [ 'cat_post_name' => $sqlOpLike, 'cat_post_slug' => $sqlOpLike, 'cat_post_description' => $sqlOpLike ],
        'coloumns_search_placeholder' => 'Find by Name or Description',
        'coloumns' => [
                'cat_post_name' => [
                        'title' => trans('admin.name'),
                        'sort' => true,
                ],
                'parentName' => [
                        'title' => trans('admin.parent'),
                        'sort' => false,
                        'filter_field' => 'cat_post__parent_id',
                        'filter_dropdown' => $listAncestor,
                ],
                'cat_post_description' => [
                        'title' => trans('admin.description'),
                        'sort' => false,
                        'isDesc' => true,
                ],
                'cat_post_status' => [
                        'title' => trans('admin.status'),
                        'sort' => false,
                        'filter_field' => 'cat_post_status',
                        'filter_dropdown' => $listStatus,
                        'callback_row'=>function($model){
                            $status = CategoryPost::getStatusName($model->cat_post_status);
                            $label = CategoryPost::getLabelClassCssStatus($model->cat_post_status);
                            return '<span class="label label-'.$label.' label-mini">' . $status . '</span>';
                        }
                ],
                'cat_post_created_at' => [
                        'title' => trans('admin.created_at'),
                        'sort' => true
                ],
        ]
]);
                
```



License
----

MIT
