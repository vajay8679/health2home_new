<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Mail;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use App\Models\FcmNotification;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Doctor;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function send_mail($mail_header,$subject,$to_mail){
        Mail::send('mail_templates.forgot_password', $mail_header, function ($message)
         use ($subject,$to_mail) {
            $message->from(env('MAIL_USERNAME'), env('APP_NAME'));
            $message->subject($subject);
            $message->to($to_mail);
        });
    }
    
    public function send_fcm($title,$description,$token){
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);
        $optionBuilder->setPriority("high");
        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($description)
                            ->setSound('default')->setBadge(1);
        
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_data']);
        
        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();
        
        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
        
        return $downstreamResponse->numberSuccess();
    }
    
    public function str_replace($find,$replace,$string){
        return str_replace($find,$replace,$string);
    }
    
    public function find_fcm_message($slug,$customer_id,$vendor_id,$partner_id){
        $message = FcmNotification::where('slug',$slug)->first();
        //print_r($message);exit;
        if($customer_id){
            $fcm_token = Customer::where('id',$customer_id)->value('fcm_token');
            if($fcm_token){
                $this->send_fcm($message->customer_title, $message->customer_description, $fcm_token);
            }
        }
        
        if($vendor_id){
            $fcm_token = Vendor::where('id',$vendor_id)->value('fcm_token');
            if($fcm_token){
                $this->send_fcm($message->vendor_title, $message->vendor_description, $fcm_token);
            }
        }
        
        if($partner_id){
            $fcm_token = DeliveryBoy::where('id',$partner_id)->value('fcm_token');
            if($fcm_token){
                $this->send_fcm($message->partner_title, $message->partner_description, $fcm_token);
            }
        }
        
    }
    
    public function find_fcm_message_doctor($slug,$doctor_id,$patient_id){
        $message = FcmNotification::where('slug',$slug)->first();
        //print_r($message);exit;
        if($doctor_id){
            $fcm_token = Doctor::where('id',$doctor_id)->value('fcm_token');
            if($fcm_token){
                $this->send_fcm($message->doctor_title, $message->doctor_description, $fcm_token);
            }
        }
    }    
    
    public function send_chat_pusher_one($fcm_token,$name,$message){
        $this->send_fcm('Message from Dr.'.$name, $message, $fcm_token);
    }
    
    public function send_chat_pusher_two($fcm_token,$name,$message){
        $this->send_fcm('Message from '.$name, $message, $fcm_token);
    }
}
