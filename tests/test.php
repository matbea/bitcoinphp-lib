<?php

require __DIR__ . "/../vendor/autoload.php";

$txs = [
    '00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295',
    '000000329877c7141c6e50b04ed714a860abcb15135611f6ac92609cb392ef60',
    '0000003d2bd90849b084e0d04afb33751a6187da86c424a6ed4b3ae5faf35313',
    '0000004a3ab8ada24ddf88c0cb94ea27d2d962db8c0aeeb35c5759342d326ef0',
    '0000004d6a850c95fcd3accdb2007542ad469b60eba76bf2c206d4cc563d93b0',
    '0000005d77a9a24d61889e9987b633ed5696f3a07c655eced8bebb147242abde',
    '0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655',
    '0000006f870414ecfc375dc6b5e51f0daad0bfb867cb49c0e63350ceac2c68a9',
    '0000007118a4d0a8eaaf31e9176f878e8f72dca5d58da1b2e67e7810c33dc9f6',
    '0000007ba8609f0acc2be539d628c246fddf945abf34c4af2028d4e60658bbb4',
    '0000008a1019e5c847530586d3282e8da6f1f87a26330c822f47a89e150cf503',
    '0000008b9f06ed2aa550b7e2efdd0abba73d619b917a77862b1954712075644d',
    '000000ad183cbae30594e0f06e90c57f3eee89f5a5aaac5170fa1ecc105a4f86',
    '000000d603e00e2d7dd3f53c02454719dc37fd5f47dc02a4a8b6f574e12f8bbd',
    '000000f870e3bbe4dc89519908c5160f91bcaf92bce66aeb7c3cce820eb357a6',
    '000001161e80043b3f2c9335eafd2666694ddcfb5236c653c60017479490b9be',
    '0000013066c9aaad31fb8eb52207779129b5881b6ba2b6d596d38f8746e5318e',
    '00000131c44a031d59e7248c256876271700d3ae268e32fd6348b30d92de1869',
    '0000017e95b848f84dc4122b97696c0d42368e3ffc65a0b845647027e6cbaa6e',
    '0000018c2f1887331db58050af9890a666649ad21b4536553de0c9b997fe3a31',
    '000001a632f6baa2cedb5b7d193bea9e76a0abb02da59fb7abd8179ea02a7b75',
    '000001b2c847b1aa29ef5f332509ff038788ab2eec566767266f54d4d3d8287d',
    '000001c631e8f30052e27414c69c9cf10eb0a0720c5282670efbef6529f69e35',
    '000001c805ea20e83919bede0c5170604d2e681c54da6fd0074b478e072681b4',
    '000001ccbe55f2467c74b986a10c584a7916b572187d62e90003b338f054ec20',
    '000001f7d15b50afe94597efea5e7f1a7a33a238e0ac0e10ce976ae88db01dde',
    '0000021838dec177b6cd38833aa52d5cee9e6cb183c6ed47b91837be300e41bf',
    '0000021b96f71321c68014e1611bc7c9919846dd16fd09801290b75c061fe0f7',
    '0000021e4b67431b13ab829bca850957b10a5cffa08e0bca5705cc20930a5478',
    '0000022287999d1af5028ee66d16c73e21e144edf62ab87287c3ee9789b8ff7b',
    '000002279c93d79a9c978fd31e8c185b03f5f247c524d0e146b9ff639a7e63c6',
    '0000022bec91129e7fdb9eb5e5867c75a7a1d365365d6eefb3abd94392f1a513',
    '00000233ab28ec14bb466f577219168ffde677ea07402b7808ba5e98eb035e0d',
    '00000246bb036fe76eaf0989230bd93d7e8c201f65f71fc46c920e90a55e2ae4',
    '0000024769291ed734fe2aece98da80dfe58fbd8010bf310f541ee77541d3473',
    '000002482829255c49452088f2fa73d15a1ebc7921eb4b196b720c0641dd6941',
    '0000024d8cec2a6e29b05231eb5b90f9105ad129b2f135c834b343ec87a36846',
    '0000027299a6459906df392269620213009ba3dac30fc62b16c26e5e6e1561bf',
    '00000286d55d3d4612346ff00a6358f790aa0b05e0e4f138485e3ff7a13da3aa',
    '000002aa1ac0ba088cb31a7ea9e1d625c9164360ccb4a40889c74567a9f44f6c',
    '000002cedb1e3337733745f23e4c5ccdc31867aaa7da4895fd047782d8808bab',
    '000002f3d047fc680a326c21fd7d057394c663301ed0992f704062a215f759e0',
    '000002fc671979702b94f4372d272bae152a6bd89d1831392ac6a11265d31f31',
    '000003048e10305ab2867321a7e818e8653af60fd5628989c67fec9cc73f44e9',
    '0000030fb4a9a7b9ad1bd5112eacde04a306f3f5e268098c9e8f1f813bb461e9',
    '0000031c3cd1d66cbd061d8156e6589944959799d2e1952dcec7500b4dda354b',
    '0000032275696c2fb8f93e5b9d22be352d984a31985475d1e74736c378e8683f',
    '00000334480293ea81971278e62917e5dd0f738839617206ddb3fe36de170d76',
    '0000033826c8c4ddffdfcb9ded4a9b418857c4d5cc1efcfd456b2f9d0226b9fd',
    '0000033e9ff3d8606f1708cca65fc6bda1f56ace5513003bc415ededb9afe311',
    '0000034d1fe0d48337c48ff4e43d7efee08b66309e0586075b0a204f8546c9a5',
    '0000035e2b0c54c24b3066fa3d56a9edc2dcfc0f6a62ac01e39233d2baadf99b',
    '0000036ae8fbfa786365f89bc1e5709101f69138747638550bd041d6a1ffc55b',
    '0000036dafc4ca9b4fa4f729937dd1416de081b695d706f3d17d856817b12e47',
    '000003afb4038d43fc7457b91028de52ff7d1864a7ae6add3a5a86f0fab5d578',
    '000003cdca1e1e75f3f8d408308440a5b1237864615ef1da9a749f29c5a0e991',
    '000003ec9c70cb3a0343da0798ce2fcfbf321cf65d880be4b48e6a38bc074005',
    '000003f4245471a4fd4f5e9eed5db600c4ef1c6b8de9e11d82d41aa5ea71ef16',
    '000003f8fd200bba6acc3dbe5610fa0d0bd868d1130910b06a4b14c55cd07d16',
    '000003fd6f35cd64a028eb49d1c9b593379fafcf4b9ced8c389257073f0dcafb',
    '0000040a85343b7fea0c21ce2b76dc4efa911555f73548437bc59db672392a49',
    '000004102645310943cdefc0e67b33d81e4120fe89b37fe1e91d23d2c2b37643',
    '0000041d8869dd940d8a62f99014136c2741d68f9a05714ac5b1b2c15a1f4be6',
    '00000430d3e8a9e16a644ad651fa926adf657190e246ed0cd20b72f50fd5fa26',
    '0000047d29b685768b4de68f0081f3cf1584b49642cd7cfcd0e58395db7861a5',
    '0000048020e8ab839923552315ad2bded92e451a6bdf0d1f2e8591284bd60260',
    '0000049a0e4d2fc1cd959e4c13385bd2cffaca3f2ca3c677d37f57dc02e7f335',
    '000004aaa7aacdd062c2ee56ae30c2cfc6b5245c931605c35b5a3857eef9aa4f',
    '000004ae99590851e847e653e277809fd153807ac3dbcb15737c80f5d20f3198',
    '000004b367a27999319c0ceded65efb7f46b1b24d98774a7fdb4f835af7aa281',
    '000004e2ff337bafd727fd3f2123d67618f0cbea5ce5ca7a180e7023fed54bcb',
    '000004e3a954d2342b7cf823b07cf08d401b4fef2119ecdbd1a0c4349f681c74',
    '00000501c49c634f79657f1a2e5952eb465ab4667b17928f66adc97e0cc15ad1',
    '0000050ab106f2d96de83aff7855cf9382164f6c306e7d14b2678c4d07bfcd28',
    '0000051af8b9bcb2b69c4afdc3fdfcf58be92929a5724ef4696e2a7adf659b70',
    '0000053dc6b53f1e8df19ce9848c10803b690aa5d69fea04ddf7bff4f7246ee4',
    '00000565ee1c58eb141e13c223e3d464138ac546c31da278398df589362976da',
    '000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb',
    '0000057889708cb169f9c7fd6fa6084dacd6345319b2c5c12cb2296c54f48086',
    '0000057dc0817fc7523ac2f54e4bfa865ecaf050457365bda6cfafdcc2fcb1ba',
    '00000594962a4359ffefa5a9f52e5f36a3de4f6983ea73f4331ac80cebb37e04',
    '000005aa852ecb214ef9fd21c4d4ff516b68263d6de64c7968e8e2551d6f0443',
    '000005b9581ac2fc850f010ad094c326b11b9a1906ac8f9fcca5fec6a62c34fd',
    '000005c5a86e5a705733991f131a9b589cc018652a4dcd2995420677201d7aba',
    '000005cf48cb98a5820f102f8e6bfcd51b3d25b058d7f061ba11dc7dc03414f3',
    '000005d1c4d9843a002b51c6cf4bc273bc2d7fdbbb338bbfccb9478346b96f0f',
    '000005d82b4265a7b9f52fc9a747150dfa53bbbf83fb070685ed2b5a9a1f300e',
    '000005fbf9a7ba80cefb87ccdea3f284b8ebf61722f1e749216306301290d536',
    '000006045caef5405cb359333ba4d469b05561bed4c7cc1b97f6dd4354bc36a1',
    '000006076e2723b160dfd3a3cd61d6d1297530a9bb8493c220ab90d3fb4c2eeb',
    '0000061e05f34132962d1de816ebcaf5123e0b651cbcb63d64de7a4154a1d569',
    '0000061f80295176a8bbda03c3112d2c21b74adaa1189a0cc850a710af64be68',
    '000006337cb3a238127976b8f20cd4e480e24d4457870a09d7b9fea97226e934',
    '000006960a39d5d55f1dfe41c3edb7186e66b81f38dfec93930daa46bf8561d1',
    '0000069d201db550375dfab21719c23e064f57a78ea874c8c7bb4f4f1b14aacb',
    '000006b5207da0c9d3b1599e1ee9f97a1c446b7dc007e301d40274669ac6c3fa',
    '000006b59217545730ca4d009f4f3e8385ebef851f3c4dacedc67ba2866d8aae',
    '000006c36a0e8e0b44467bc190182751c0982bec9f92ffad33315abce47e76e2',
    '000006e502f83a00677b7acb800ee6df08d5f78408ccd3c1036099e4e460ad5e',
    '000006e57ce90db7c9cfc3ad45a1a3cea4617626ff666ea289103a2b82aae27a'
];

try {
    $logger = new Monolog\Logger('BTCBridge');
    $logfilename = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "c:/ProgramData/btcbridge.log" : '/tmp/btcbridge.log';
    $logger->pushHandler(new Monolog\Handler\StreamHandler($logfilename));
    //$blockCypherHandler = (new \BTCBridge\Handler\BlockCypherHandler())->setToken("dc20a175f3594965a8f4707cdcf58a32");
    $blockTrailHandler = (new \BTCBridge\Handler\BlockTrailHandler())->setToken("11ea0d1712216a4b8ef5da969111178459e5d02f");
    $matbeaHandler = (new \BTCBridge\Handler\MatbeaHandler())->setToken("bvdb0uqv3ukr93ks9iis07so5639sit5");

    $bridge = new \BTCBridge\Bridge(
        [
            $blockTrailHandler
            ,
            $matbeaHandler
            //,
            //$blockCypherHandler
            //,
            //(new \BTCBridge\Handler\BlockCypherHandler())->setToken("dc20a175f3594965a8f4707cdcf58a32")
        ],
        new \BTCBridge\ConflictHandler\DefaultConflictHandler(),
        new \BTCBridge\ResultHandler\DefaultResultHandler(),
        $logger
    );

    $bridge->setOption(\BTCBridge\Bridge::OPT_LOCAL_PATH_OF_WALLET_DATA, __DIR__ . "/data/wallet.dat");

    //$res = $bridge->gettransactions(["7890a6b1d38741a5b019e34c8576165195af9e7b9af7935024b150e870f530d2"]);
    //array_splice($txs, 20, 20);
    //$txs = ["82e406c824b89dd1b6aa1d49e6af6a7420d2bc30be1e3f783ddebeb9ee0da64e"];
    //$txs = ["d92a7814dd15f4380cd3f85e3efb24d887773eacecf0a604e23f0267eb70b48a"];
    //$txs = ["000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb"];
    //$txs = ["0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f"];
    //$txs = ["0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03"];
    /*$txs = [
        '000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f',
        '00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295',
        '0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03',
        '0000561d7d43a41a75a9ff78bba64f0d6dc3b1709aae58522f5f7eb11fec27a2',
        '000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb',
        '0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655'
    ];
    $txs = ["d01a99b5cf7b258d1431822b0fe151772023ca525269369ce6157bf6e32041cb"];
    $txs = ["0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f"];
    $txs = ["0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f"];
    $txs = ["0000fa31b80b4da025c65c32f06881e321d8eefcd949e0e96deb6e43b9bfc219"];*/

    //$res = $bridge->gettransactions(["000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb"]);
    //$res = $bridge->gettransactions(["2d05f0c9c3e1c226e63b5fac240137687544cf631cd616fd34fd188fc9020866"]);
    //die;

    /*$chunks = array_chunk($txs, 20);
    foreach ( $chunks as $chunk ) {
        $res = $bridge->gettransactions($chunk);
    }
    die;*/
    //$res = $bridge->listtransactions("deadushka1");

    //$txs = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655","00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"],[]);
    /*$txs = [
        '0000354c3112e062f26df428ab831ff33ae2aca1381982931a33e40c778cbca2',
        '00004973ccacb026732e8751ef8e9dedd1706b1cf27d5308a5097fd98cca09e7',
        '0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f', //CRASH
        '0000bf2e205a47994c1a9a0525d1d1658216b036baa1fe6bc8d50417e1ac61a8',
        '0000e58684b78dfd555ade10a49d043d3a2d059940a3a8e2a2b6e15ddfe1153b',
        '0000fa31b80b4da025c65c32f06881e321d8eefcd949e0e96deb6e43b9bfc219', //CRASH
        '00010dea7f32ece989228fc2b8d3209aa165f74742bf14c5435edd3d27239b4e',
        '00011c29277fc948629be3f9c00fd26d11ec19b952402d1b0df7658fdb33ebeb'  //CRASH
    ];*/
    //$txs = array_slice($txs, 0, 3);
    //$txs = $bridge->gettransactions($txs,[]);
    //foreach ( $txs as $tx ) {
        //$r = $bridge->gettransactions([$tx],[]);
    //}
    //die;



    //$res = $bridge->getnewaddress();
    //$res = $bridge->dumpprivkey("1GC5nxT5cUASbqMcCkB94ZvH6C1pq6eADg");

    //$balance = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655","00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"],[]);

    //curl -k "https://api.blockcypher.com/v1/btc/main/txs/00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295;21df512a116abb22384bfe47f15833e43ac4f8999b434a7a5c74ad1f487043a9?token=dc20a175f3594965a8f4707cdcf58a32" > 2
    //curl -k "https://api.blockcypher.com/v1/btc/main/txs/0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098;9b0fc92260312ce44e74ef369f5c66bbb85848f2eddd5a7a1cde251e54ccfdd5;999e1c837c76a1b7fbb7e57baf87b309960f5ffefbf2a9b95dd890602272f644;df2b060fa2e5e9c8ed5eaf6a45c13753ec8c63282b2688322eba40cd98ea067a;63522845d294ee9b0188ae5cac91bf389a0c3723f084ca1025e7d9cdfe481ce1;20251a76e64e920e58291a30d4b212939aae976baca40e70818ceaa596fb9d37;8aa673bc752f2851fd645d6a0a92917e967083007d9c1684f9423b100540673f;a6f7f1c0dad0f2eb6b13c4f33de664b1b0e9f22efad5994a6d5b6086d85e85e3;0437cd7f8525ceed2324359c2d0ba26006d92d856a9c20fa0241106ee5a597c9;d3ad39fa52a89997ac7381c95eeffeaf40b66af7a57e9eba144be0a175a12b11;f8325d8f7fa5d658ea143629288d0530d2710dc9193ddc067439de803c37066e;3b96bb7e197ef276b85131afd4a09c059cc368133a26ca04ebffb0ab4f75c8b8;9962d5c704ec27243364cbe9d384808feeac1c15c35ac790dffd1e929829b271;e1afd89295b68bc5247fe0ca2885dd4b8818d7ce430faa615067d7bab8640156;50748b7a193a0b23f1e9494b51131d2f954cc6cf4792bacc69d207d16002080d;e79fc1dad370e628614702f048edc8e98829cf8ea8f6615db19f992b1be92e44;a3e0b7558e67f5cadd4a3166912cbf6f930044124358ef3a9afd885ac391625d;f925f26deb2dc4696be8782ab7ad9493d04721b28ee69a09d7dfca51b863ca23;9b9e461221e5284f3bfe5656efdc8c7cc633b2f1beef54a86316bf2ae3a3e230;ee1afca2d1130676503a6db5d6a77075b2bf71382cfdf99231f89717b5257b5b;e0175970efb4417950921bfcba2a3a1e88c007c21232ff706009cc70b89210b4;9e2eaf1d7e5178a2d116f331c340f1f7fd6de7540783ac36a7054fe9a4d64943;e1cf3476234d8446653ad52a8939ed792003eefdcd0e897319ab9d2cb4c14c8c;43c39b8b3728c6cb26fae0fc18803ca6cf43e15fde218e3dfbd54e633cf6753e;9b0f52332d7d013b49016416c4818b5abb80b01ca526b7b813830348ad2321be;67c1e8143bb6ad221a4ce77d6c8be68f2e25e0743f51b2db1a7b22bab59014dc;230cf03a6ce420eaa42e3c64feebb47920f3470efb4323b4574b4b6e5a004f65;f399cb6c5bed7bb36d44f361c29dd5ecf12deba163470d8835b7ba0c4ed8aebd;c361e2f4581f035dd58b99788347884e046e47b4c17ec347344ff8b24cd377ec;f5e26c8b82401c585235c572ba8265f16f7d9304ed8e31c198eab571754f5331;86b33edba8ff663b0f73ef487e4433f34d26ef91de15659d2cc09594d27b52cb;2b9905f06583c01454f10f720b5709e3b667c9dd3d9efc423c97b7e70afdc0c9;bdeaa0089cd84670da5e6385f0185c2d7978bf57a1aa5540d3ff3b3eabaa1210;223b0620a8f1c1f23a2ebf8032ed11321b921017d01974e74cddd651319b5474;852b1997ed935ba638078998e2d15bc8a91b8ad232e2d988e22c969eba3bafe0;d05d256fbd5845b30039e37d48215788a4e438249048c47ddb9c83cd927d4d5a;6d344eb5d67ed329a1c1d7603bba4b85d5916435b49f7a585bb370b76820287d;e690daeb9f73d29d8a22cb4b5ec29970e9b32283d4376adeaad8691ccb449a68;1484c18ba443b13851098597d8cb6d49d5983eab63c53d6b0dcc565612e7ca6b;04391286b3aefbb5df4cdb515ac7fce7942525fa602e1d7757e90a4fd41a1e20;9efa6cb3b8cca3c9387144f397f80e7b4bc2dd86026fdd308625a2e100a08d5a;27c4d937dca276fb2b61e579902e8a876fd5b5abc17590410ced02d5a9f8e483;2f5c03ce19e9a855ac93087a1b68fe6592bcf4bd7cbb9c1ef264d886a785894e;439aee1e1aa6923ad61c1990459f88de1faa3e18b4ee125f99b94b82e1e0af5f;f69778085f1e78a1ea1cfcfe3b61ffb5c99870f5ae382e41ec43cf165d66a6d9;ddd4d06365155ab4caaaee552fb3d8643207bd06efe14f920698a6dd4eb22ffa;d17b9c9c609309049dfb9005edd7011f02d7875ca7dab6effddf4648bb70eff6;32edede0b7d0c37340a665de057f418df634452f6bb80dcb8a5ff0aeddf1158a;194c9715279d8626bc66f2b6552f2ae67b3df3a00b88553245b12bffffad5b59;540a7e54fd64478554519f1b2d643ecc888c5030631487f9cfc530b71d281309;a09c89d2a31440658a42ec97aeee0d36b01529e45b315922e2aa2955334d1821;b3f978e6ee5e91662af30a634b3f3268c6f5d34aac1eb54e6cc9535026f5084f;3c6f58905f06a9fe9b4313a88827c43dbfb0156fcc3b17bb311d51e8be6746f5;d3dc9be5d58809579e56cdd64e78257ecd24e2726cf26ef0a698f4e5c07670b5;19521c62482ac299b37c8ae4eab67abd150e3c21dc66b7e2b0919444769ee2ed;fe9ccc6c8b44c67ab170135d4148d6424e748f7549547d8174d59b5127df0102;bc0f0f8b3235421a036122299db7046a00c3a7cd1d650de3d92969780b873728;9635054e3de101ea39dcfa5f4cec63ceb1205db6e0a99304c8db2b3a162137e4;3c0db11484606a63b04ef4b8adcd665655939d3932ab1ebe90d1dfc5f077ca95;82a1e1731a9b22fdea55c09f2fac191a89efee127956fcfef65caab70f54002d;ae6eedf8e47ac6dda10ca9f3334bd3031795c55a124948003acd944ebd31fee1;701ce76c033e0b03fa79503770a5874840373e30cd9c1eca472ec66617f3a3ee;0731f33cf07a2e5f749a2910c437a015968e1fc2ed79c95634829167db280c4f;ebdb8335d5b148e9cc1b1bb795ee619a649bd1638e5514fdfc3004b1c56fd6a4;f15c7c51118c1dc93129a9f4999c7768ef2ff8cda3230f56d32576518c5f9349;f01d7897ca02ed20dfb5544b3c1228e225fd97b72510d40af6c9d8e489033a74;b17fc28c1dc15ca4b05bf4784419c145b14e79ece54c793656997984c4e46715;bdc5121446fd203abbf0e4d13fe99594dc3dc4c1cb95504feb459e8982d923be;807822a80b4ed7b7dfbe560a79705c62a71e48be9dbcce152ab70f94b1eb4307;aa5f3068b53941915d82be382f2b35711305ec7d454a34ca69f8897510db7ab8;fd83acf6e35be34a654ae53fbdc43ae4a7c4e1bcc334064a6360bf6b32339fcb;dea3224cc1333edf41b3b97913d8b2a6402bddf6132f2137e003b535369f9ae1;41b48c64cba68c21e0b7b37f589408823f112bb7cbccef4aece29df25347ffb4;d472546c5171ea73bc9000f2994f069631c7706f5e922e2fe5f8e50e0402bed6;382501ac2d50c5944465c2c316dbe2c70f23dd0de73ea86d339ea5f2bca7b648;47c981c4193d62d9d33b59fe1a6de9c27ff78d9f9003325b61e686a720673626;b5015619429af022efa69e0401891a92533b1852f214b68504c2e435a8cb7d0e;7ea1d2304f1f95fae773ed8ef67b51cfd5ab33ea8b6ab0a932ee3e248b7ba74c;fb9967077804b9a4b7f5c80f7d8c567639cc14b17955da27c10b9947ddbd1825;5ed287fa7b07229b53b15c6ad95ab49c1c222aa0fcfa2dd6603fa8492fae54c6;5c4c653395879f5992949b3e28bb0be7b27d49e9e11d64899c5de30928d4ad99;81fb4b1430e5c2cdee38be1a147c45fe23b38bbad0375ff67dc64450844445be;561eebc615218a12a9588ac78dde63aa56f3b1b43eb17174d2f093d1e2d1ca73;9a3d8485cde248ab7dcea68c5a8539498ecb8ad337f2574fc2143d2f644b2e1d;c39bde3039372f835ca96f15d5f38df585fac24a316d28cb5febae7c4af15cb4;12272d79ff1fbc1238d78d7d6e0e75c5f0e7aae7925e4f59b96b02fc2cd82bfc;edb7b78f88263e5c47717ee0761131683e85cc31beeac9a98554917c30686440;797556eb999f8fdfd8198dd9ea80723409a359ddcd75c4a54a511970745812f0;2306da842919574ed10ee9c211508b2bae2e8729b2fa5e83b4173831b89d2c28;9f94cc5c9211258c39f048f4da42dd3048e4d1434c2eff2d28dbf3b3612775c6;c184c93d7494f4096e9e9cb420f7d74c79c4ffd2806d68ff03bb7633bbf21d67;81b896b98663fb6238c9643545eba3a1b32933f96e1a33d122efed5ab7165f2d;71cbe112176d6dc40490dde588798bd75de80133438016a0c05754d74ee1925a;3226a75aaf23565ab1f4b4d3cf7f945e188b1ef35d14bba50bd3ee05df985490;8f2cd8d8c2b4ac9162cd8fb11856982ca1bb33898d19f6b50d8361b1318b7dc0;5f716bfbb5be57a4f2283509938898cffe7971af12adb152a058ac02405c85d8;cc4d0dd009ccf08805d9dca9232a47c60681375312a520f3609045649c2a1690;d0d31488af1205e17630a459d0e2874d18e3102bee42b4dd3806dd9169638b66;33a4592d4d8dc04304361ad7bcd96fe23fa801db6d7d917074f75c732079a569?token=dc20a175f3594965a8f4707cdcf58a32" > 2

    //$res = $bridge->listunspent("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");

    //$res = $bridge->listunspent("stst");
    //$res = $bridge->listunspent("19LtvgLf6ciPVLNAFqfQ3bpbmz1h7nGbjE");
    //$res = $bridge->getbalance("19LtvgLf6ciPVLNAFqfQ3bpbmz1h7nGbjE");
    //$res = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655","00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"],[]);

    $wallet = new \BTCBridge\Api\Wallet;
    //$wallet->setSystemDataByHandler($blockCypherHandler->getHandlerName(), ["name"=>"deadushka"]);
    //$bridge->deleteWallet($wallet);
    /*$wallet = $bridge->createWallet("deadushka1",['1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    $addresses = $bridge->getAddresses($wallet);
    $wallet = $bridge->addAddresses($wallet,['1BdxBor4JG76RKLAwJZfHC58fWbgidYukz','1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    $addresses = $bridge->getAddresses($wallet);
    $wallet = $bridge->removeAddresses($wallet,['1BdxBor4JG76RKLAwJZfHC58fWbgidYukz','1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    $bridge->deleteWallet($wallet);
    die;*/




    /*$res = $bridge->gettransactions(["21df512a116abb22384bfe47f15833e43ac4f8999b434a7a5c74ad1f487043a9"]); //unconfirmed
    $res = $bridge->gettransactions(["000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f"]); //different time
    $res = $bridge->gettransactions(["a1405d6b266b63a2d1a5af6b3dee1af9ae60124be16f81b4774059c7dd43aa27"]); //newly minted OK
    $res = $bridge->gettransactions(["000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f"]); //newly minted OK
    $res = $bridge->gettransactions(["00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"]); //pubkeyhash
    $res = $bridge->gettransactions(["0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03"]); //multisig !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    $res = $bridge->gettransactions(["0000561d7d43a41a75a9ff78bba64f0d6dc3b1709aae58522f5f7eb11fec27a2"]); //nonstandard
    $res = $bridge->gettransactions(["000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb"]); //nulldata
    $res = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655"]); //scripthash
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>1]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>2]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>1,"outstart=1000"]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>2,"outstart=2000"]); //13106 outputs (tx_id=101172926)*/


    /*$wallet = new \BTCBridge\Api\Wallet();
    $wallet->setSystemDataByHandler($blockCypherHandler->getHandlerName(), ["name"=>"todo5"]);
    //$addresses = $bridge->getAddresses($wallet);
    $wallet->setSystemDataByHandler($matbeaHandler->getHandlerName(), ["id"=>"123","name"=>"todo5"]);
    $wallet = $bridge->createWallet("todo5", ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz","12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"]);
    $bridge->deleteWallet($wallet);

    //$wallet = $bridge->addaddresses($wallet, ["12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"]);
    //$wallet = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz;12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");
    $wallet = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");
    $wallet = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");

    $res = $bridge->sendrawtransaction("0100000001c94a679002e334674ad4d4e56deaaf3c6e7df1700c11812f319342c59641b8150".
    "10000006b483045022100df9befbf00083719716e03310bceed664e7810b27eac884559f6dc6a4fe05dd7022060d4126d70ff399a9f90a".
    "d2352e837497163aacf50d61f423c2b8924bb537aec01210267af6c6bf4ae6e37f019fbfbc7df70acf48663adbf19161bd874f3babd6bf".
    "15c00000000027c150000000000001976a914df5d6e3c76eb3f38744fbe3b4a4f32aaaf7d607088ac5c951200000000001976a9140fb50".
    "d2ec6bb62bd690bb55142101ca28a678be188ac00000000", 0);


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
    //$res = $bridge->gettransactions(["a1405d6b266b63a2d1a5af6b3dee1af9ae60124be16f81b4774059c7dd43aa27"]);
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
        '0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f', //CRASH
        '0000bf2e205a47994c1a9a0525d1d1658216b036baa1fe6bc8d50417e1ac61a8',
        '0000e58684b78dfd555ade10a49d043d3a2d059940a3a8e2a2b6e15ddfe1153b',
        '0000fa31b80b4da025c65c32f06881e321d8eefcd949e0e96deb6e43b9bfc219', //CRASH
        '00010dea7f32ece989228fc2b8d3209aa165f74742bf14c5435edd3d27239b4e',
        '00011c29277fc948629be3f9c00fd26d11ec19b952402d1b0df7658fdb33ebeb'//CRASH
    ];
    foreach ($txs as $txhash) {
        $res = $bridge->gettransactions([$txhash]);
    }

    //$res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>PHP_INT_MAX,"outstart=2000"]); //13106 outputs (tx_id=101172926)


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
    */
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
