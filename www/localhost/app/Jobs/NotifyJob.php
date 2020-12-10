<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotifyJob extends Job
{
    private $param;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * 给下游回调的异步任务
     *
     * @return void
     */
    public function handle()
    {
        $param = $this->param;
        for ($i = 0; $i < 3; $i++) {
            Log::info("下游回调参数" . json_encode($param['param']));
            $res = httpPost($param['url'], $param['param']);
            if ($res == "OK") {
                // DB::table('orders')->where('pay_orderid','=',$param['param']['transaction_id'])->update(['status'=>4]);
                break;
            }
        }
    }
}
