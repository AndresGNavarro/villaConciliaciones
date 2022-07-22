<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailUserRegistered extends Mailable
{
    use Queueable, SerializesModels;
    public $name;
    public $email;
    public $password;

    public function __construct($name,$email,$password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
       
    }
    /* En esta funcion retornamos la vista que forma el correo a enviar y agregamos el encabazado a uno de sus parametros */
    public function build()
    {
      
        return $this->view('mails.viewMailUserRegistered')->subject('Alta Usuario para Sistema de Conciliaciones Villatours');
      
    }
}