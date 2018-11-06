<?php
require __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/pusher.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

$payload = json_decode($_POST['payload']);

$commits = array();
$json = array();

try {

    foreach ($payload->commits as $key => $value) {

        $aux = array();

        $aux['id'] = $payload->commits[$key]->id;
        $aux['message'] = $payload->commits[$key]->message;
        $aux['timestamp'] = floatval(strtotime($payload->commits[$key]->timestamp)) * -1;
        $aux['author_name'] = $payload->commits[$key]->author->name;
        $aux['author_email'] = $payload->commits[$key]->author->email;
        $aux['pusher_name'] = $payload->pusher->name;

        $aux['files_modified'] =  $payload->commits[$key]->modified;
        $aux['files_removed'] =  $payload->commits[$key]->removed;
        $aux['files_added'] =  $payload->commits[$key]->added;

        $aux['repository'] = array();
        $aux['repository']['name'] = $payload->repository->name;
        $aux['repository']['email'] = $payload->repository->owner->email;
        $aux['repository']['ower_name'] = $payload->repository->owner->name;
        $aux['repository']['full_name'] = $payload->repository->full_name;

        $commits[] = $aux;
        // break;
    }

    if (empty($commits)) {
        $json['status'] = false;
        $json['message'] = "Nenhum commit foi encontrado";

        echo json_encode($json);

        exit;
    }

    // This assumes that you have placed the Firebase credentials in the same directory
    // as this PHP file.
    $serviceAccount = ServiceAccount::fromJsonFile(__DIR__.'/files/google-services.json');

    $firebase = (new Factory)->withServiceAccount($serviceAccount)
    ->withDatabaseUri('https://notification-app-7da05.firebaseio.com/')
    ->create();

    $database = $firebase->getDatabase();
    $users = $database->getReference('users')->getSnapshot()->getValue();

    $fullName = $payload->repository->full_name;

    $token = "";

    foreach ($users as $user) {
        if ($user['username'] == $payload->sender->login)
            $token = $user['github_token'];
    }

    if (empty($token)) {
        $json['status'] = false;
        $json['message'] = "Não foi possivel encontrar o token do usuario";

        echo json_encode($json);

        exit;
    }

    $client = new \Github\Client();
    $client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);
    $repositories = $client->api('repo')->contributors($payload->repository->owner->name, $payload->repository->name);

    $usersNames = array();

    foreach ($repositories as $repository) {
        $usersNames[] = $repository['login'];
    }

    foreach ($users as $user) {

        if (in_array($user['username'], $usersNames)) {
            foreach ($commits as $commit) {
                $postRef = $database->getReference("commits/{$user['uid']}/{$commit['id']}")->set($commit);
            }
        }

        // if ($user['username'] != $payload->pusher->name)
        sendPusher($user['registrationid'], "Um novo evento foi resgistrado em seu repositório!", $payload->repository->name);
    }

    $json['status'] = true;
    echo json_encode($json);

} catch (\Exception $e) {
    $database->getReference("logs")->push($e->getMessage() . PHP_EOL . $e->getLine());
}
