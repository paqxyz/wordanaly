<?php

namespace App\Jobs;

use App\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class InsertDbJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logs = DB::select('select * from log where id=?',[$this->id]);
        if ($logs) {
            $log = $logs[0];
            $files = config('filesystems.disks.admin.root').DIRECTORY_SEPARATOR.$log->file;

            //解析cvs文件
            if (($handle = fopen($files, "r")) !== FALSE) {
                //创建新库
                $day = date('Ymd', strtotime($log->d));
                DB::statement('create table if not exists word_' . $day . ' like word;');
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    try{
                        if (isset($data[1]) && isset($data[1])) {
                            $keyword['name'] = @iconv('gb2312', 'utf-8//TRANSLIT//IGNORE', trim($data[0]));
                            $keyword['ranking'] = @iconv('gb2312', 'utf-8//TRANSLIT//IGNORE', trim($data[1]));
                            $keyword['url'] = isset($data[10]) ? @iconv('gb2312', 'utf-8//TRANSLIT//IGNORE', trim($data[10])) : ' ';
                            //入库
                            DB::insert('insert into word_' . $day . ' (keyword, ranking, url, siteid, date) values (?, ?, ?, ?, ?)', [$keyword['name'], $keyword['ranking'], $keyword['url'], $log->siteid, $log->d]);
                        }
                    } catch (Exception $e) {
                        \Illuminate\Support\Facades\Log::info($e->getMessage());
                    }
                }
                DB::update('update log set status=1 where id=?', [$this->id]);
                fclose($handle);
            }
        }
    }
}
