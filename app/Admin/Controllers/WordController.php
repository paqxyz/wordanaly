<?php

namespace App\Admin\Controllers;

use App\Jobs\InsertDbJobs;
use App\Log;
use App\Site;
use App\Word;

use DeepCopy\Filter\Filter;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Alert;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;


use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Chart\Bar;
use Encore\Admin\Widgets\Chart\Doughnut;
use Encore\Admin\Widgets\Chart\Line;
use Encore\Admin\Widgets\Chart\Pie;
use Encore\Admin\Widgets\Chart\PolarArea;
use Encore\Admin\Widgets\Chart\Radar;
use Encore\Admin\Widgets\Collapse;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Symfony\Component\VarDumper\Cloner\Data;

class WordController extends Controller
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
            $content->header('查询');
            $content->description('比对数据...');
            $content->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $form = new \Encore\Admin\Widgets\Form();
                    $form->select('siteid', '站点')->options(Site::all()->pluck('sitename', 'id'))
                        ->load('start', '/admin/ajaxlogdate')
                        ->load('end', '/admin/ajaxlogdate');
                    $form->select('start', '起始日期')->options(Log::where([['siteid','=', 1],['status','=',1]])->orderBy('d','desc')->pluck('d', 'd'));
                    $form->select('end', '结束日期')->options(Log::where([['siteid','=', 1],['status','=',1]])->orderBy('d','desc')->pluck('d', 'd'));
                    $form->select('type', '功能')->options([1=>'新增比对']);
                    $form->action('/admin');
                    $column->append($form);
                });
            });

            if (isset($_POST['start']) && isset($_POST['end'])) {
                $headers = ['ID', '热词', '排名', '链接'];
                $start = date('Ymd', strtotime($_POST['start']));
                $end = date('Ymd', strtotime($_POST['end']));
                //$page = $_POST['page'] ? $_POST['page'] : 1;
                //$perPage = 50;

                switch ($_POST['type']) {
                    case 1:
                        if ($_POST['start'] == $_POST['end']) {
                            $rows = DB::select('SELECT id,keyword,ranking,url FROM word_'.$end.' WHERE siteid='.$_POST['siteid'].' limit 1000');
                        } else {
                            $rows = DB::select('SELECT ws.id,ws.keyword,ws.ranking,ws.url FROM word_'.$end.' AS ws LEFT JOIN word_'.$start.' AS we ON we.keyword=ws.keyword AND we.siteid=ws.siteid WHERE ws.siteid='.$_POST['siteid'].' AND we.keyword IS NULL ORDER BY ws.ranking limit 1000');
                        }
                        break;
                    default:
                        break;
                }

                foreach ($rows as $key=>$val) {
                    $rows[$key]->url = '<a href="'.$val->url.'" target="_blank">'.$val->url.'</a>';
                }
                //$pages = new Paginator($rows, $perPage);
                $content->row((new Box('对比数据', new Table($headers, $rows)))->style('info')->solid());
            }
        });
    }

    public function ajaxlogdate()
    {
        $siteid = $_GET['q'];
        return json_encode(Log::where([['siteid','=', $siteid],['status','=',1]])->orderBy('d','desc')->pluck('d', 'd'));
    }
    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('header');
            $content->description('description');
            $content->body($this->form());
        });
    }


    /**
     * Make a grid builder.
     *
     * @return Grid

    protected function grid()
    {
        return Admin::grid(Word::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->keyword('热词');
            $grid->ranking('排名');

            $grid->disableCreation();
            $grid->disableActions();
            $grid->disableBatchDeletion();
            //$grid->disablePagination();


            $grid->filter(function($filter) {
                //$filter->useModal();

                // sql: ... WHERE `user.email` = $email;
                $filter->is('siteid', '站点')->select(Site::all()->pluck('sitename', 'id'));

                // sql: ... WHERE `user.created_at` BETWEEN $start AND $end;
                $filter->between('date', '日期')->datetime();
            });

            if (isset($_GET['date']['start'])) {
            //    $grid->rows(function ($rows){return null;});
                //$start = date('Ymd', strtotime($_GET['date']['start']));
                //$grid->model()->crossJoin($grid->model()->getTable().'_'.$start);
                //$grid->model()->where('id', '>', $_GET['id']);
                //$grid->model()->take(100);
            }
        });
    }
     * /

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Word::class, function (Form $form) {
            $form->select('选择站点')->options(function () {
                $sites = Site::all();

                foreach ($sites as $site) {
                    $temp[$site->id] = $site->sitename;
                }
                return $temp;
            });
            $form->date('日期');
            $form->file('上传文件');

            //入库前逻辑
            $form->saving(function (Form $form) {
                if ($_FILES['上传文件']['error']==0 && ($_FILES['上传文件']['type'] == 'application/vnd.ms-excel' || $_FILES['上传文件']['type'] == 'text/csv') && $_POST['日期']) {
                    if (DB::select('SELECT * FROM log WHERE sourcefile= ? AND d= ?', [$_FILES['上传文件']['name'], $_POST['日期']])) {
                        exit('当前文件已经提交过，请勿重复提交！');
                    }
                    if (DB::select('SELECT * FROM log WHERE siteid= ? AND d= ?', [$_POST['选择站点'], $_POST['日期']])) {
                        exit('当前站点日期下的数据已经提交过，请勿重复提交！');
                    }
                    $files = config('filesystems.disks.admin.root').DIRECTORY_SEPARATOR.date('YmdHis').'.csv';
                    move_uploaded_file($_FILES['上传文件']['tmp_name'], $files);

                    //解析cvs文件
                    if (file_exists($files)) {
                        $id = DB::table('log')->insertGetId(['siteid'=>$_POST['选择站点'], 'd'=>$_POST['日期'], 'file'=>basename($files), 'sourcefile'=>$_FILES['上传文件']['name'], 'created_at'=>time()]);
                        dispatch((new InsertDbJobs($id))->onConnection('beanstalkd'));

                        return redirect('/admin/logs');
                    } else {
                        exit('文件上传失败！');
                    }
                } else {
                    exit('文件格式不正确或者参数不完整！');
                }
            });

        });
    }

    //推送失败测试
    public function test()
    {
        //补任务
        for ($i=38; $i<=64; $i++) {
            dispatch((new InsertDbJobs($i))->onConnection('beanstalkd'));
        }
       // dispatch((new InsertDbJobs(37))->onConnection('beanstalkd'));

        /* 错误测试
        $logs = DB::select('select * from log where id=?',[13]);
        if ($logs) {
            $log = $logs[0];
            $files = config('filesystems.disks.admin.root').DIRECTORY_SEPARATOR.$log->file;

            //解析cvs文件
            if (($handle = fopen($files, "r")) !== FALSE) {
                //创建新库
                $day = date('Ymd', strtotime($log->d));
                DB::statement('create table if not exists word_' . $day . ' like word;');
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                        if (isset($data[1]) && isset($data[1])) {
                            $keyword['name'] = @iconv('gb2312', 'utf-8//TRANSLIT//IGNORE', trim($data[0]));
                            $keyword['ranking'] = @iconv('gb2312', 'utf-8//TRANSLIT//IGNORE', trim($data[1]));
                            $keyword['url'] = isset($data[10]) ? @iconv('gb2312', 'utf-8//TRANSLIT//IGNORE', trim($data[10])) : ' ';
                            $keyword['url'] = strlen($keyword['url'])>=200 ? substr($keyword['url'], 0, 199) : $keyword['url'];
                            //if ($keyword['name'] == '学习软件免费下载') {
                                echo $keyword['name'].'</br>';
                            //}
                            //入库
                            if (!DB::select('select id from word_' . $day . ' where siteid=? and keyword=?', [$log->siteid, $keyword['name']])) {
                                DB::insert('insert into word_' . $day . ' (keyword, ranking, url, siteid, date) values (?, ?, ?, ?, ?)', [$keyword['name'], $keyword['ranking'], $keyword['url'], $log->siteid, $log->d]);
                            }
                        }

                }
                DB::update('update log set status=1 where id=?', [13]);
                fclose($handle);
            }
        }*/
    }
}
