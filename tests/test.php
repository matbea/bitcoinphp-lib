<?php

require __DIR__ . "/../vendor/autoload.php";

try {
    $logger = new Monolog\Logger('BTCBridge');
    $logfilename = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "c:/ProgramData" : '/tmp/btcbridge.log';
    $logger->pushHandler(new Monolog\Handler\StreamHandler($logfilename), Monolog\Logger::DEBUG);

    $bridge = new \BTCBridge\Bridge(
        [
            (new \BTCBridge\Handler\BlockCypherHandler())->setToken("dc20a175f3594965a8f4707cdcf58a32")
            ,
            new \BTCBridge\Handler\MatbeaHandler()
        ],
        new \BTCBridge\ConflictHandler\DefaultConflictHandler(),
        $logger
    );

    $bridge->setOption(\BTCBridge\Bridge::OPT_LOCALPATH2WALLETDATA, __DIR__ . "/data/wallet.dat");

    //$addr = $bridge->getnewaddress("sdf");
    $wif = $bridge->dumpprivkey("sdf1", "1JTypCDWN7a7GNHaZdwjUkGQyFwhFqTuGL");

    //It will raise the exception , because % sybol is contained in the string
    $res = $bridge->createwallet("deadka1-%_", []);
    $res = $bridge->createwallet(
        "testname1000",
        ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", "1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7"]
    );
    $res = $bridge->addaddresses("testname1000", ["12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"]);

    $res = $bridge->gettransaction("721dca6852f828af1057d5bf5f324a6d2b27328a27882229048cf340c1e3ec10");
    $res = $bridge->gettransaction("a1405d6b266b63a2d1a5af6b3dee1af9ae60124be16f81b4774059c7dd43aa27");
    $res = $bridge->listtransactions("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy", []);
    $res = $bridge->listtransactions("deadka", [ /*"before"=>10,"after"=>20*/]);
    $res = $bridge->getbalance("deadka", 4000);
    $res = $bridge->getunconfirmedbalance("deadka");
    $res = $bridge->listunspent("deadka");
    $res = $bridge->listunspent("deadka", 0); //"12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"
    //$res = $bridge->sendrawtransaction("0100000001c94a679002e334674ad4d4e56deaaf3c6e7df1700c11812f319342c59641b8150".
    //"10000006b483045022100df9befbf00083719716e03310bceed664e7810b27eac884559f6dc6a4fe05dd7022060d4126d70ff399a9f90a".
    //"d2352e837497163aacf50d61f423c2b8924bb537aec01210267af6c6bf4ae6e37f019fbfbc7df70acf48663adbf19161bd874f3babd6bf".
    //"15c00000000027c150000000000001976a914df5d6e3c76eb3f38744fbe3b4a4f32aaaf7d607088ac5c951200000000001976a9140fb50".
    //"d2ec6bb62bd690bb55142101ca28a678be188ac00000000", 0);
} catch (\InvalidArgumentException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\RuntimeException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\BTCBridge\Exception\ConflictHandlerException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\Exception $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
}
