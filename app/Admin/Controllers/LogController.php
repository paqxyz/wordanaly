<?php

namespace App\Admin\Controllers;

use App\Log;

use App\Site;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class LogController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('导入历史');
            $content->description('已经导入数据列表');

            $content->body($this->grid());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Log::class, function (Grid $grid) {
            $grid->id('ID')->sortable();
            $grid->siteid('站点')->value(function($siteid) {
                return Site::find($siteid)->sitename;
            });

            $grid->d('日期');
            $grid->created_at('导入时间');
            $grid->status('导入状态')->value(function ($status) {
                return $status ? '<span style="color: green">导入成功</span>' : '<span style="color: red">正在导入…</span>';
            });
            $grid->disableCreation();
            $grid->disableBatchDeletion();
            $grid->disableActions();

        });
    }

}
