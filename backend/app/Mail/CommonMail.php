<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommonMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($html, $subject, $attachment_info ='', $attchment='', $attach_file_name = '')
    {
        $this->html = $html;
        $this->subject = $subject;
        $this->attachment_info = $attachment_info;
        $this->attchment = $attchment;
        $this->attach_file_name = $attach_file_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->html($this->html)->subject($this->subject);

        if($this->attchment && $this->attach_file_name){
            $this->attachData($this->attchment, $this->attach_file_name);
        }
        if($this->attachment_info){
            foreach ($this->attachment_info as $filePath) {
                $mail->attach($filePath);
            }
        }
        return $mail;
    }
}
