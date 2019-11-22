<?php
namespace app\index\controller;

use ai\face\recognition\Client;

class Index
{
    public function index()
    {


        // user_1
        $image1 = 'http://face.worthcloud.net/2019-04-08-10-09-30_00_0024_0022_164_164.jpeg';
        $image2 = 'http://face.worthcloud.net/2019-04-08-10-15-39_00_0018_0026_164_164.jpeg';
        $image3 = 'http://face.worthcloud.net/2019-04-08-08-54-31_00_0037_0044_208_208.jpeg';
        $image4 = 'http://face.worthcloud.net/2019-04-08-10-33-20_00_0032_0035_200_200.jpeg';
        $image5 = 'http://face.worthcloud.net/2019-04-08-08-43-46_00_0011_0034_172_172.jpeg';

        // user_2
        $image6 = 'http://face.worthcloud.net/2019-04-08-09-20-50_00_0016_0042_168_168.jpeg';
        $image7 = 'http://face.worthcloud.net/2019-04-08-08-47-55_00_0028_0018_136_136.jpeg';

        // user_3
        $image8 = 'http://face.worthcloud.net/2019-04-28-10-41-58_00_0894_0342_116_146.jpeg';
        $image9 = 'http://face.worthcloud.net/2019-04-28-10-42-00_00_1296_0418_224_280.jpeg';

        $client = new Client(config('haibo'));

        echo '<pre>';
//         $res = $client->driver('baidu')->detect($image1);

         $res = $client->driver('baidu')->compare($image8, $image9);
//
//         $res = $client->driver('tencent-cloud')->detect($image1);

        // $res = $client->driver('tencent-cloud')->liveVerify([$image9]);

        // $res = $client->driver('tencent-cloud')->compare($image8, $image9);

        // $res = $client->driver('tencent-cloud')->createGroup('group_1', '用户组1');

        // $res = $client->driver('tencent-cloud')->addUser($image1, 'user_1', 'group_1', '用户1');

        // $res = $client->driver('tencent-cloud')->search($image1, ['group_1']);

        // $res = $client->driver('tencent-cloud')->updateUser('user_1', 'YongHu1');

//         $res = $client->driver('tencent-cloud')->getUser('user_1');

        // $res = $client->driver('tencent-cloud')->addFace('user_1', $image2);

        // $res = $client->driver('tencent-cloud')->deleteFace('user_1', '3098685752384485977');

        // $res = $client->driver('tencent-cloud')->deleteUser('user_1');

//        $res = $client->driver('baidu')->liveVerify([$image1]);

        print_r($res);
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }

    public function test1()
    {
        $faceApi = new \app\common\api\BaseFaceApi();
        $storeId=1;
        $fuserId = 10;
        $res = $faceApi->_workLog($storeId, $fuserId, $captureTime=time(), 'http://face.worthcloud.net/2019-04-08-0-54-31_00_0037_0044_208_208.jpeg', '1234');
        var_dump($res);
    }
}
