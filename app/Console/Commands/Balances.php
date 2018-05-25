<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Balances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $info = $this->wex_query('getInfo');
        if($info['success']){
            foreach ($info['return']['funds'] as $fund => $balance){
                if($balance > 0){
                    $this->info($fund . ': ' . $balance);
                }
            }
        }
    }

    function wex_query($method, array $req = array())
    {
        // API settings
        $key = env('WEX_KEY', ''); // your API-key
        $secret = env('WEX_SECRET', ''); // your Secret-key

        $req['method'] = $method;
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1];

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');

        $sign = hash_hmac('sha512', $post_data, $secret);

        // generate the extra headers
        $headers = array(
            'Sign: ' . $sign,
            'Key: ' . $key,
        );

        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; BTCE PHP client; ' . php_uname('s') . '; PHP/' . phpversion() . ')');
        }
        curl_setopt($ch, CURLOPT_URL, 'https://wex.nz/tapi/');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // run the query
        $res = curl_exec($ch);
        if ($res === false) {
            $this->warn('Could not get reply: ' . curl_error($ch));
            return;
        }
        $dec = json_decode($res, true);
        if (!$dec){
            $this->warn('Invalid data received, please make sure connection is working and requested API exists');
            return;
        }
        return $dec;
    }
}
