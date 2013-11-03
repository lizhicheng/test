<?php
/**
 * Email相关类
 *
 * @author lizhicheng <li_zhicheng@126.com>
 *
 */
class CEmail
{

    /**
     * 发送email
     *
     * @param $to 送达email地址
     * @param $subject email主题
     * @param $body email正文
     * @param $phpmail 是否采用php的mail函数发送
     *            0 采用phpemailer发送
     *            1 采用php的mail函数发送
     *
     * @return bool
     *
     */
    public static function sendEmail($to, $subject, $body, $phpmail = 0)
    {
        if ($phpmail == 1) {
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=gb2312' . "\r\n";
            $headers .= 'From: xxx@xxx.com' . "\r\n" . 'Reply-To: webmaster@example.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion();

            @mail($to, $subject, $body, $headers);
            return;
        }

        $smtp = Li::config('smtp');

        require APP_ROOT . '/include/phpmailer/class.phpmailer.php';
        $mail = PHPMailer;
        $mail = new PHPMailer();

        $mail->IsSMTP();
        $mail->Host = $smtp['host'];
        $mail->Mailer = "smtp";
        $mail->From = $smtp['from'];
        $mail->FromName = $smtp['fromname'];
        $mail->AddAddress($to);

        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];

        $mail->WordWrap = 50;
        $mail->IsHTML(true);

        $mail->CharSet = 'utf-8';
        $mail->Subject = $subject;
        $mail->Body = $body;

        if (! $mail->Send()) {
            // Li::log("Send Email Error: " . $mail->ErrorInfo);
            return false;
        }
        return true;
    }

    /**
     * 发送密码至邮箱
     *
     * @param $to 送达email地址
     * @param $pwd 密码串
     *
     * @return bool
     *
     */
    public static function emailpwd($to, $pwd)
    {
        $emailpwd = Li::lang('emailpwd');

        $subject = $emailpwd['subject'];
        $body = sprintf($emailpwd['body'], $pwd);

        return self::sendEmail($to, $subject, $body);
    }
}

?>