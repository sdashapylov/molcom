<?php
require_once __DIR__ . '/../vendor/autoload.php';
use phpseclib3\Net\SFTP;

class MolcomSftpSender
{
    protected $host;
    protected $user;
    protected $pass;
    protected $remote_dir;

    public function __construct($host, $user, $pass, $remote_dir)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->remote_dir = $remote_dir;
    }

    public function send($filename, $file_content)
    {
        $sftp = new SFTP($this->host);
        if (!$sftp->login($this->user, $this->pass)) {
            throw new Exception("Не удалось авторизоваться на SFTP");
        }
        if (!$sftp->chdir($this->remote_dir)) {
            throw new Exception("Не удалось открыть папку $this->remote_dir на сервере");
        }
        if (!$sftp->put($filename, $file_content)) {
            throw new Exception("Ошибка при загрузке файла $filename");
        }
        return true;
    }
}