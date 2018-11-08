<?php
require './../vendor/autoload.php';
include_once __DIR__ . '/pusher.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

$payload = json_decode($_POST['payload']);

$commits = array();
$json = array();

$headers = getallheaders();

function commitEvent ($payload, $json) {
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
                    //$postRef = $database->getReference("commits/{$user['uid']}/{$commit['id']}")->set($commit);
                }
            }

            // if ($user['username'] != $payload->pusher->name)
            //sendPusher($user['registrationid'], "Um novo evento foi resgistrado em seu repositório!", $payload->repository->name);
        }

        $json['status'] = true;
        echo json_encode($json);

    } catch (\Exception $e) {
        $database->getReference("logs")->push($e->getMessage());
    }
}

function issueEvent ($payload, $json) {
    try {
        $issue = array();

        $issue['number'] = $payload->issue->number;
        $issue['sender'] = $payload->sender->login;
        $issue['state'] = $payload->issue->state;
        $issue['title'] = $payload->issue->title;
        $issue['body'] = $payload->issue->body;
        $issue['created_at'] = floatval(strtotime($payload->issue->created_at)) * -1;
        $issue['repository'] = $payload->repository->name;

        if ($payload->action == 'closed')
            $issue['closed_at'] = floatval(strtotime($payload->issue->closed_at)) * -1;

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
        $repositories = $client->api('repo')->contributors($payload->repository->owner->login, $payload->repository->name);

        $usersNames = array();

        foreach ($repositories as $repository) {
            $usersNames[] = $repository['login'];
        }

        foreach ($users as $user) {
            if (in_array($user['username'], $usersNames)) {
                $postRef = $database->getReference("issues/{$user['uid']}/{$issue['number']}")->set($issue);
            }

            if ($user['username'] != $payload->sender->login)
            sendPusher($user['registrationid'], "Um novo evento foi resgistrado em seu repositório!", $payload->repository->name);
        }

        $json['status'] = true;
        echo json_encode($json);

    } catch (\Exception $e) {
        print_r($e);
        $database->getReference("logs")->push($e->getMessage());
    }
}

switch ($headers['X-GitHub-Event']) {
    case 'issues':
        issueEvent($payload, $json);
    break;

    case 'push':
        commitEvent($payload, $json);
    break;

    default:
        echo json_encode(array("status" => false, "message" => "Evento nao reconhecido"));
    break;
}
