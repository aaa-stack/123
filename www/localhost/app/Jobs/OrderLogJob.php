<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;

class OrderLogJob extends Job
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
        $data = [
            'pay_orderid' => $param['pay_orderid'],
            'merchant_no' => $param['pay_memberid'],
            'param' => json_encode($param),
            'ip' => get_client_ip(),
            'time' => time()
        ];
        DB::table("order_log")->insert($data);
    }
}
