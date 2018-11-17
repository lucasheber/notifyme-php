<?php
/**
 * Created by PhpStorm.
 * User: lucas
 * Date: 07/11/18
 * Time: 18:36
 */

namespace Source\App;


class SendNotification
{

    private $registrationId;
    private $body;
    private $title;
    private $icon;
    private $largeIcon;
    private $smallIcon;
    private $vibrate;
    private $keyApi;
    private $headers;
    private $arrSender;
    private $fields;

    private $trigger;

    private static $URL_FCM = 'https://fcm.googleapis.com/fcm/send';

    /**
     * SendNotification constructor.
     * @param $registrationId
     * @param $body
     * @param $title
     * @param $keyApi
     */
    public function __construct($registrationId, $body, $title, $keyApi)
    {
        $this->registrationId = $registrationId;
        $this->body = $body;
        $this->title = $title;
        $this->keyApi = $keyApi;
    }

    /**
     * @return mixed
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
    }

    /**
     * @param mixed $registrationId
     */
    public function setRegistrationId($registrationId)
    {
        $this->registrationId = $registrationId;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return mixed
     */
    public function getLargeIcon()
    {
        return $this->largeIcon;
    }

    /**
     * Absolute path of image.
     * @param mixed $largeIcon
     */
    public function setLargeIcon($largeIcon)
    {
        if (file_exists($largeIcon))
            $this->largeIcon = $largeIcon;
        else
            $this->largeIcon = '';
    }

    /**
     * @return mixed
     */
    public function getSmallIcon()
    {
        return $this->smallIcon;
    }

    /**
     * @param mixed $smallIcon
     */
    public function setSmallIcon($smallIcon)
    {
        $this->smallIcon = $smallIcon;
    }

    /**
     * @return mixed
     */
    public function getVibrate()
    {
        return $this->vibrate;
    }

    /**
     * @param mixed $vibrate
     */
    public function setVibrate($vibrate)
    {
        $this->vibrate = $vibrate;
    }

    /**
     * Send a notification message to device.
     *
     * @throws \Exception if the values are not valid.
     * @return bool true on success or false on failure.
     */
    public function sender()
    {
        $bool = false;

        try {

            if (!$this->parseInit())
                throw new \Exception($this->trigger);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->fields));

            $bool = curl_exec($ch);

            // close connection
            curl_close($ch);
        } catch (\Exception $exception) {
            throw new \Exception("Error in curl execution.", 0, $exception);
        }

        return $bool;
    }// sender

    private function parseInit(): bool
    {
        $bool = false;

        if (empty($this->keyApi)) {
            $this->trigger = 'Key Api is empty!';

        } elseif (empty($this->registrationId)) {
            $this->trigger = 'Registration ID is empty!';

        } else {

            $this->headers = ["Authorization: key={$this->keyApi}", 'Content-Type: application/json'];

            $this->arrSender = array(
                'body' => $this->body,
                'title' => $this->title,
                'icon' => $this->icon,
                'vibrate' => $this->vibrate,
                'sound' => $this->sound,
                'largeIcon' => $this->largeIcon,
                'smallIcon' => $this->smallIcon
            );

            $this->fields = ['to' => $this->getRegistrationId(), 'notification' => $this->arrSender];

            $bool = true;
        }

        return $bool;
    }// parseInit

}
