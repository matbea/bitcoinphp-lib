<?php

require __DIR__ . "/../vendor/autoload.php";

try {
    $logger = new Monolog\Logger('BTCBridge');
    $logfilename = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "c:/ProgramData/btcbridge.log" : '/tmp/btcbridge.log';
    $logger->pushHandler(new Monolog\Handler\StreamHandler($logfilename), Monolog\Logger::DEBUG);
    $blockCypherHandler = (new \BTCBridge\Handler\BlockCypherHandler())->setToken("dc20a175f3594965a8f4707cdcf58a32");
    $matbeaHandler = new \BTCBridge\Handler\MatbeaHandler();

    $bridge = new \BTCBridge\Bridge(
        [
            $blockCypherHandler
            ,
            $matbeaHandler
            //,
            //(new \BTCBridge\Handler\BlockCypherHandler())->setToken("dc20a175f3594965a8f4707cdcf58a32")
        ],
        new \BTCBridge\ConflictHandler\DefaultConflictHandler(),
        new \BTCBridge\ResultHandler\DefaultResultHandler(),
        $logger
    );

    $bridge->setOption(\BTCBridge\Bridge::OPT_LOCAL_PATH_OF_WALLET_DATA, __DIR__ . "/data/wallet.dat");

    $res = $bridge->listunspent("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");


    $res = $bridge->gettransaction("21df512a116abb22384bfe47f15833e43ac4f8999b434a7a5c74ad1f487043a9"); //unconfirmed
    $res = $bridge->gettransaction("000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f"); //different time
    $res = $bridge->gettransaction("a1405d6b266b63a2d1a5af6b3dee1af9ae60124be16f81b4774059c7dd43aa27"); //newly minted OK
    $res = $bridge->gettransaction("000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f"); //newly minted OK
    $res = $bridge->gettransaction("00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"); //pubkeyhash
    $res = $bridge->gettransaction("0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03"); //multisig !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    $res = $bridge->gettransaction("0000561d7d43a41a75a9ff78bba64f0d6dc3b1709aae58522f5f7eb11fec27a2"); //nonstandard
    $res = $bridge->gettransaction("000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb"); //nulldata
    $res = $bridge->gettransaction("0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655"); //scripthash
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>1]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>2]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>1,"outstart=1000"]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>2,"outstart=2000"]); //13106 outputs (tx_id=101172926)


    $wallet = new \BTCBridge\Api\Wallet();
    $wallet->setSystemDataByHandler($blockCypherHandler->getHandlerName(), ["name"=>"todo5"]);
    //$addresses = $bridge->getAddresses($wallet);
    $wallet->setSystemDataByHandler($matbeaHandler->getHandlerName(), ["id"=>"123","name"=>"todo5"]);
    $wallet = $bridge->createWallet("todo5", ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz","12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"]);
    $bridge->deleteWallet($wallet);

    //$wallet = $bridge->addaddresses($wallet, ["12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"]);
    //$wallet = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz;12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");
    $wallet = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");
    $wallet = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");



    //$balance = $bridge->getbalance("1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");

    //$addr = $bridge->getnewaddress("sdf");
    //$wif = $bridge->dumpprivkey("sdf1", "1JTypCDWN7a7GNHaZdwjUkGQyFwhFqTuGL");

    //It will raise the exception , because % sybol is contained in the string
    //$res = $bridge->createWallet("deadka1-%_", []);

    //$wallet = new BTCBridge\Api\Wallet();
    //$wallet->setSystemDataByHandler($blockCypherHandler->getHandlerName(), ["name"=>"todo"]);
    //$wallet->setSystemDataByHandler($matbeaHandler->getHandlerName(), ["id"=>"123","name"=>"todo"]);
    //$bridge->deleteWallet($wallet);
    //$bridge->deleteWallet($wallet);
    //$wallet = $bridge->createWallet("todo", ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz"]);
    //$wallet = $bridge->createWallet("todo", ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz"]);
    //$wallet = $bridge->addaddresses($wallet, ["12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"]);
    //$res = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");
    //$addresses = $res = $bridge->getAddresses($wallet);

    //$res = $bridge->listtransactions("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy", []);
    //$res = $bridge->gettransaction("a1405d6b266b63a2d1a5af6b3dee1af9ae60124be16f81b4774059c7dd43aa27");
    //$balance = $bridge->getbalance("1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");
    //$unconfirmedbalance = $bridge->getunconfirmedbalance("1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");

    $bridge->setOption(\BTCBridge\Bridge::OPT_MINIMAL_FEE_PER_KB, "163054");
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 1179178 - 41242 - 0);
    $sendMoneyOptions = new BTCBridge\Api\SendMoneyOptions();
    $sendMoneyOptions->setConfirmations(1);
    $sendMoneyOptions->setComment("");
    $sendMoneyOptions->setCommentTo("");
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 1179178 - 41242 - 0);

    //$smoutput = new \BTCBridge\Api\SMOutput();
    //$smoutput->setAddress("1BdxBor4JG76RKLAwJZfHC58fWbgidYukz")->setAmount(1179178 - 41242 - 0);
    //$res = $bridge->sendmanyEX("tst", [$smoutput], $sendMoneyOptions);
    //$res = $bridge->getunconfirmedbalance("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");
    //$res = $bridge->getbalance("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");



    $txs = [
        '0000354c3112e062f26df428ab831ff33ae2aca1381982931a33e40c778cbca2',
        '00004973ccacb026732e8751ef8e9dedd1706b1cf27d5308a5097fd98cca09e7',
        '0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f'/*CRASH*/,
        '0000bf2e205a47994c1a9a0525d1d1658216b036baa1fe6bc8d50417e1ac61a8',
        '0000e58684b78dfd555ade10a49d043d3a2d059940a3a8e2a2b6e15ddfe1153b',
        '0000fa31b80b4da025c65c32f06881e321d8eefcd949e0e96deb6e43b9bfc219'/*CRASH*/,
        '00010dea7f32ece989228fc2b8d3209aa165f74742bf14c5435edd3d27239b4e',
        '00011c29277fc948629be3f9c00fd26d11ec19b952402d1b0df7658fdb33ebeb'/*CRASH*/
    ];
    foreach ($txs as $txhash) {
        $res = $bridge->gettransaction($txhash);
    }

    //$res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>PHP_INT_MAX,"outstart=2000"]); //13106 outputs (tx_id=101172926)


    die;
    //$res = $bridge->addaddresses("tst", ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz"]);
    //$res = $bridge->getAddresses("tst");
    //$res = $bridge->listunspent("tst");

    //$res = $bridge->getAddresses("deadka");
    //$res = $bridge->getAddresses("tst");


    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 1202384);
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 5500 + 2530);
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 13894 - 2530);
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 13894 - 2530 - 0);
    //$bridge->settxfee(70000);
    //$smoutput = new \BTCBridge\Api\SMOutput();
    //$smoutput->setAddress("1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7")->setAmount(5500);
    //$res = $bridge->sendmany("tst", [$smoutput]);



    //$res = $bridge->getAddresses("deadka");

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
} catch (\BTCBridge\Exception\HandlerErrorException $ex) {
    $successHandlers = $ex->getSuccessHandlers();
    $errorHandler = $ex->getErrorHandler();
    $unusedHandlers = $ex->getUnusedHandlers();
    $result = $ex->getResult();
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\BTCBridge\Exception\ConflictHandlerException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\BTCBridge\Exception\ResultHandlerException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\Exception $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
}
