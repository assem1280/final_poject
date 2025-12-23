<?php
/**
 * PHPMailer - PHP email creation and transport class.
 * PHP Version 5.5+
 * 
 * This is a simplified version for basic email functionality.
 * For full features, download the complete PHPMailer from:
 * https://github.com/PHPMailer/PHPMailer
 */

namespace PHPMailer\PHPMailer;

class PHPMailer
{
    const VERSION = '6.8.0';
    const CHARSET_ASCII = 'us-ascii';
    const CHARSET_ISO88591 = 'iso-8859-1';
    const CHARSET_UTF8 = 'utf-8';
    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';
    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_BINARY = 'binary';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';
    const ICAL_METHOD_REQUEST = 'REQUEST';
    const ICAL_METHOD_PUBLISH = 'PUBLISH';
    const ICAL_METHOD_REPLY = 'REPLY';
    const ICAL_METHOD_ADD = 'ADD';
    const ICAL_METHOD_CANCEL = 'CANCEL';
    const ICAL_METHOD_REFRESH = 'REFRESH';
    const ICAL_METHOD_COUNTER = 'COUNTER';
    const ICAL_METHOD_DECLINECOUNTER = 'DECLINECOUNTER';

    public $Priority;
    public $CharSet = self::CHARSET_UTF8;
    public $ContentType = self::CONTENT_TYPE_PLAINTEXT;
    public $Encoding = self::ENCODING_8BIT;
    public $ErrorInfo = '';
    public $From = '';
    public $FromName = '';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $Ical = '';
    public $MIMEBody = '';
    public $MIMEHeader = '';
    public $mailHeader = '';
    protected $WordWrap = 0;
    public $Mailer = 'mail';
    public $Sendmail = '/usr/sbin/sendmail';
    public $UseSendmailOptions = true;
    public $ConfirmReadingTo = '';
    public $Hostname = '';
    public $MessageID = '';
    public $MessageDate = '';
    public $Host = 'localhost';
    public $Port = 25;
    public $Helo = '';
    public $SMTPSecure = '';
    public $SMTPAutoTLS = true;
    public $SMTPAuth = false;
    public $SMTPOptions = [];
    public $Username = '';
    public $Password = '';
    public $AuthType = '';
    public $Timeout = 300;
    public $dsn = '';
    public $SMTPDebug = 0;
    public $Debugoutput = 'echo';
    public $SMTPKeepAlive = false;
    public $SingleTo = false;
    public $do_verp = false;
    public $AllowEmpty = false;
    public $DKIM_selector = '';
    public $DKIM_identity = '';
    public $DKIM_passphrase = '';
    public $DKIM_domain = '';
    public $DKIM_copyHeaderFields = true;
    public $DKIM_extraHeaders = [];
    public $DKIM_private = '';
    public $DKIM_private_string = '';
    public $action_function = '';
    public $XMailer = '';
    public static $validator = 'php';
    
    protected $smtp;
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $ReplyTo = [];
    protected $all_recipients = [];
    protected $RecipientsQueue = [];
    protected $ReplyToQueue = [];
    protected $attachment = [];
    protected $CustomHeader = [];
    protected $lastMessageID = '';
    protected $message_type = '';
    protected $boundary = [];
    protected $language = [];
    protected $error_count = 0;
    protected $sign_cert_file = '';
    protected $sign_key_file = '';
    protected $sign_extracerts_file = '';
    protected $sign_key_pass = '';
    protected $exceptions = false;
    protected $uniqueid = '';

    const STOP_MESSAGE = 0;
    const STOP_CONTINUE = 1;
    const STOP_CRITICAL = 2;

    const CRLF = "\r\n";
    const FWS = ' ';
    const MAIL_MAX_LINE_LENGTH = 63;
    const MAX_LINE_LENGTH = 998;
    const STD_LINE_LENGTH = 76;

    public function __construct($exceptions = null)
    {
        if (null !== $exceptions) {
            $this->exceptions = (bool)$exceptions;
        }
        $this->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };
    }

    public function __destruct()
    {
        $this->smtpClose();
    }

    private function mailPassthru($to, $subject, $body, $header, $params)
    {
        if ((int)ini_get('mbstring.func_overload') & 1) {
            $subject = $this->base64EncodeWrapMB($subject, "\n");
        }
        if ($this->UseSendmailOptions && !empty($params)) {
            return mail($to, $subject, $body, $header, $params);
        }
        return mail($to, $subject, $body, $header);
    }

    public function isHTML($isHtml = true)
    {
        if ($isHtml) {
            $this->ContentType = static::CONTENT_TYPE_TEXT_HTML;
        } else {
            $this->ContentType = static::CONTENT_TYPE_PLAINTEXT;
        }
    }

    public function isSMTP()
    {
        $this->Mailer = 'smtp';
    }

    public function isMail()
    {
        $this->Mailer = 'mail';
    }

    public function isSendmail()
    {
        $ini_sendmail_path = ini_get('sendmail_path');
        if (false === stripos($ini_sendmail_path, 'sendmail')) {
            $this->Sendmail = '/usr/sbin/sendmail';
        } else {
            $this->Sendmail = $ini_sendmail_path;
        }
        $this->Mailer = 'sendmail';
    }

    public function isQmail()
    {
        $ini_sendmail_path = ini_get('sendmail_path');
        if (false === stripos($ini_sendmail_path, 'qmail')) {
            $this->Sendmail = '/var/qmail/bin/qmail-inject';
        } else {
            $this->Sendmail = $ini_sendmail_path;
        }
        $this->Mailer = 'qmail';
    }

    public function addAddress($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('to', $address, $name);
    }

    public function addCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('cc', $address, $name);
    }

    public function addBCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('bcc', $address, $name);
    }

    public function addReplyTo($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('Reply-To', $address, $name);
    }

    protected function addOrEnqueueAnAddress($kind, $address, $name)
    {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        
        $pos = strrpos($address, '@');
        if (false === $pos) {
            $error_message = sprintf('%s (%s): %s', $this->lang('invalid_address'), $kind, $address);
            $this->setError($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }
            return false;
        }
        
        $params = [$kind, $address, $name];
        if (array_key_exists($address, $this->all_recipients)) {
            return false;
        }
        
        switch ($kind) {
            case 'to':
                $this->to[] = $params;
                break;
            case 'cc':
                $this->cc[] = $params;
                break;
            case 'bcc':
                $this->bcc[] = $params;
                break;
            case 'Reply-To':
                $this->ReplyTo[] = $params;
                break;
        }
        
        $this->all_recipients[$address] = true;
        return true;
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));
        
        $pos = strrpos($address, '@');
        if (false === $pos || (!$this->has8bitChars(substr($address, ++$pos)) || !static::idnSupported()) && !static::validateAddress($address)) {
            $error_message = sprintf('%s (From): %s', $this->lang('invalid_address'), $address);
            $this->setError($error_message);
            if ($this->exceptions) {
                throw new Exception($error_message);
            }
            return false;
        }
        
        $this->From = $address;
        $this->FromName = $name;
        if ($auto && empty($this->Sender)) {
            $this->Sender = $address;
        }
        return true;
    }

    public static function validateAddress($address, $patternselect = null)
    {
        if (null === $patternselect) {
            $patternselect = static::$validator;
        }
        
        if ('php' === $patternselect) {
            return (bool)filter_var($address, FILTER_VALIDATE_EMAIL);
        }
        
        return (bool)preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $address);
    }

    public static function idnSupported()
    {
        return function_exists('idn_to_ascii') && function_exists('mb_convert_encoding');
    }

    protected function has8bitChars($text)
    {
        return (bool)preg_match('/[\x80-\xFF]/', $text);
    }

    public function clearAddresses()
    {
        $this->to = [];
        $this->all_recipients = [];
    }

    public function clearCCs()
    {
        $this->cc = [];
    }

    public function clearBCCs()
    {
        $this->bcc = [];
    }

    public function clearReplyTos()
    {
        $this->ReplyTo = [];
        $this->ReplyToQueue = [];
    }

    public function clearAllRecipients()
    {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->all_recipients = [];
        $this->RecipientsQueue = [];
    }

    public function clearAttachments()
    {
        $this->attachment = [];
    }

    public function clearCustomHeaders()
    {
        $this->CustomHeader = [];
    }

    protected function setError($msg)
    {
        ++$this->error_count;
        $this->ErrorInfo = $msg;
    }

    protected function lang($key)
    {
        $languages = [
            'invalid_address' => 'Invalid address',
            'mailer_not_supported' => 'Mailer is not supported',
            'provide_address' => 'You must provide at least one recipient email address.',
            'smtp_connect_failed' => 'SMTP connect() failed.',
            'smtp_error' => 'SMTP server error',
            'data_not_accepted' => 'SMTP Error: data not accepted.',
            'authenticate' => 'SMTP Error: Could not authenticate.',
            'from_failed' => 'The following From address failed',
            'recipients_failed' => 'SMTP Error: The following recipients failed',
            'empty_message' => 'Message body empty',
            'encoding' => 'Unknown encoding',
            'execute' => 'Could not execute',
            'file_access' => 'Could not access file',
            'file_open' => 'File Error: Could not open file',
            'signing' => 'Signing Error',
            'extension_missing' => 'Extension missing'
        ];
        
        return isset($languages[$key]) ? $languages[$key] : $key;
    }

    public function send()
    {
        try {
            if (!$this->preSend()) {
                return false;
            }
            return $this->postSend();
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
            return false;
        }
    }

    public function preSend()
    {
        if (empty($this->to) && empty($this->cc) && empty($this->bcc)) {
            $this->setError($this->lang('provide_address'));
            return false;
        }
        
        if (!empty($this->AltBody)) {
            $this->ContentType = static::CONTENT_TYPE_MULTIPART_ALTERNATIVE;
        }
        
        $this->setMessageType();
        $this->MIMEHeader = '';
        $this->MIMEBody = $this->createBody();
        $this->MIMEHeader = $this->createHeader();
        
        return true;
    }

    public function postSend()
    {
        try {
            switch ($this->Mailer) {
                case 'sendmail':
                case 'qmail':
                    return $this->sendmailSend($this->MIMEHeader, $this->MIMEBody);
                case 'smtp':
                    return $this->smtpSend($this->MIMEHeader, $this->MIMEBody);
                case 'mail':
                    return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
                default:
                    $this->setError(sprintf($this->lang('mailer_not_supported'), $this->Mailer));
                    return false;
            }
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            if ($this->exceptions) {
                throw $exc;
            }
            return false;
        }
    }

    protected function mailSend($header, $body)
    {
        $toArr = [];
        foreach ($this->to as $toaddr) {
            $toArr[] = $this->addrFormat($toaddr);
        }
        $to = implode(', ', $toArr);
        
        $result = $this->mailPassthru($to, $this->Subject, $body, $header, $this->Sender);
        
        if (!$result) {
            $this->setError('mail() returned failure');
            return false;
        }
        
        return true;
    }

    protected function smtpSend($header, $body)
    {
        $bad_rcpt = [];
        
        if (!$this->smtpConnect($this->SMTPOptions)) {
            $this->setError($this->lang('smtp_connect_failed'));
            return false;
        }
        
        $smtp_from = $this->Sender ?: $this->From;
        if (!$this->smtp->mail($smtp_from)) {
            $this->setError($this->lang('from_failed') . ': ' . $smtp_from);
            $this->smtp->quit();
            return false;
        }
        
        $callbacks = [];
        foreach ([$this->to, $this->cc, $this->bcc] as $togroup) {
            foreach ($togroup as $to) {
                if (!$this->smtp->recipient($to[1], $this->dsn)) {
                    $bad_rcpt[] = $to[1];
                    $callbacks[] = ['issent' => false, 'to' => $to[1], 'name' => $to[2]];
                } else {
                    $callbacks[] = ['issent' => true, 'to' => $to[1], 'name' => $to[2]];
                }
            }
        }
        
        if ((count($bad_rcpt) === count($this->all_recipients)) || (!$this->AllowEmpty && empty($this->all_recipients))) {
            $this->setError($this->lang('recipients_failed') . ': ' . implode(', ', $bad_rcpt));
            $this->smtp->quit();
            return false;
        }
        
        if (!$this->smtp->data($header . $body)) {
            $this->setError($this->lang('data_not_accepted'));
            $this->smtp->quit();
            return false;
        }
        
        $smtp_debug = $this->getSMTPInstance()->getLastReply();
        if (preg_match('/^[0-9]{3} *([0-9\.]+)/', $smtp_debug, $matches)) {
            $this->lastMessageID = $matches[1];
        }
        
        if (!$this->SMTPKeepAlive) {
            $this->smtp->quit();
        }
        
        foreach ($callbacks as $cb) {
            $this->doCallback(
                $cb['issent'],
                [[$cb['to'], $cb['name']]],
                [],
                [],
                $this->Subject,
                $body,
                $this->From,
                []
            );
        }
        
        return true;
    }

    public function smtpConnect($options = null)
    {
        if (null === $this->smtp) {
            $this->smtp = $this->getSMTPInstance();
        }
        
        if (null === $options) {
            $options = $this->SMTPOptions;
        }
        
        if ($this->smtp->connected()) {
            return true;
        }
        
        $this->smtp->Timeout = $this->Timeout;
        $this->smtp->do_debug = $this->SMTPDebug;
        $this->smtp->Debugoutput = $this->Debugoutput;
        $this->smtp->do_verp = $this->do_verp;
        
        $hosts = explode(';', $this->Host);
        $lastexception = null;
        
        foreach ($hosts as $hostentry) {
            $hostinfo = [];
            if (!preg_match('/^(?:(ssl|tls):\/\/)?(.+?)(?::(\d+))?$/', trim($hostentry), $hostinfo)) {
                $this->edebug($this->lang('invalid_host_entry') . ' ' . trim($hostentry));
                continue;
            }
            
            $prefix = '';
            $secure = $this->SMTPSecure;
            $tls = (static::ENCRYPTION_STARTTLS === $this->SMTPSecure);
            
            if ('ssl' === $hostinfo[1] || ('' === $hostinfo[1] && static::ENCRYPTION_SMTPS === $this->SMTPSecure)) {
                $prefix = 'ssl://';
                $tls = false;
                $secure = static::ENCRYPTION_SMTPS;
            } elseif ('tls' === $hostinfo[1]) {
                $tls = true;
                $secure = static::ENCRYPTION_STARTTLS;
            }
            
            $sslext = defined('OPENSSL_ALGO_SHA256');
            if (static::ENCRYPTION_STARTTLS === $secure || static::ENCRYPTION_SMTPS === $secure) {
                if (!$sslext) {
                    $this->setError($this->lang('extension_missing') . ': openssl');
                    return false;
                }
            }
            
            $host = $hostinfo[2];
            $port = $this->Port;
            if (array_key_exists(3, $hostinfo) && is_numeric($hostinfo[3]) && $hostinfo[3] > 0) {
                $port = (int)$hostinfo[3];
            }
            
            if ($this->smtp->connect($prefix . $host, $port, $this->Timeout, $options)) {
                try {
                    if (!$this->smtp->hello($this->Helo ?: gethostname())) {
                        throw new Exception($this->lang('smtp_error'));
                    }
                    
                    if ($tls) {
                        if (!$this->smtp->startTLS()) {
                            throw new Exception($this->lang('smtp_error'));
                        }
                        if (!$this->smtp->hello($this->Helo ?: gethostname())) {
                            throw new Exception($this->lang('smtp_error'));
                        }
                    }
                    
                    if ($this->SMTPAuth && !$this->smtp->authenticate($this->Username, $this->Password, $this->AuthType)) {
                        throw new Exception($this->lang('authenticate'));
                    }
                    
                    return true;
                } catch (Exception $exc) {
                    $lastexception = $exc;
                    $this->edebug($exc->getMessage());
                    $this->smtp->quit();
                }
            }
        }
        
        $this->smtp->close();
        if ($this->exceptions && null !== $lastexception) {
            throw $lastexception;
        }
        
        return false;
    }

    public function smtpClose()
    {
        if ((null !== $this->smtp) && $this->smtp->connected()) {
            $this->smtp->quit();
            $this->smtp->close();
        }
    }

    public function getSMTPInstance()
    {
        if (!is_object($this->smtp)) {
            $this->smtp = new SMTP();
        }
        return $this->smtp;
    }

    protected function setMessageType()
    {
        $type = [];
        if ($this->alternativeExists()) {
            $type[] = 'alt';
        }
        if ($this->inlineImageExists()) {
            $type[] = 'inline';
        }
        if ($this->attachmentExists()) {
            $type[] = 'attach';
        }
        $this->message_type = implode('_', $type);
        if ('' === $this->message_type) {
            $this->message_type = 'plain';
        }
    }

    public function createHeader()
    {
        $result = '';
        $result .= $this->headerLine('Date', '' === $this->MessageDate ? static::rfcDate() : $this->MessageDate);
        
        if ('' === $this->MessageID) {
            $this->MessageID = $this->createMessageID();
        }
        $result .= $this->headerLine('Message-ID', $this->MessageID);
        
        $result .= $this->headerLine('From', $this->addrFormat(['',$this->From, $this->FromName]));
        
        foreach ($this->to as $toaddr) {
            $result .= $this->headerLine('To', $this->addrFormat($toaddr));
        }
        
        foreach ($this->cc as $ccaddr) {
            $result .= $this->headerLine('Cc', $this->addrFormat($ccaddr));
        }
        
        $result .= $this->headerLine('Subject', $this->encodeHeader($this->secureHeader($this->Subject)));
        
        if ('' !== $this->XMailer) {
            $result .= $this->headerLine('X-Mailer', $this->XMailer);
        } else {
            $result .= $this->headerLine('X-Mailer', 'PHPMailer ' . self::VERSION . ' (https://github.com/PHPMailer/PHPMailer)');
        }
        
        $result .= $this->headerLine('MIME-Version', '1.0');
        $result .= $this->getMailMIME();
        
        return $result;
    }

    public function getMailMIME()
    {
        $result = '';
        $ismultipart = true;
        
        switch ($this->message_type) {
            case 'inline':
                $result .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $result .= $this->textLine(' boundary="' . $this->boundary[1] . '"');
                break;
            case 'attach':
            case 'inline_attach':
            case 'alt_attach':
            case 'alt_inline_attach':
                $result .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_MIXED . ';');
                $result .= $this->textLine(' boundary="' . $this->boundary[1] . '"');
                break;
            case 'alt':
            case 'alt_inline':
                $result .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
                $result .= $this->textLine(' boundary="' . $this->boundary[1] . '"');
                break;
            default:
                $result .= $this->textLine('Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet);
                $ismultipart = false;
        }
        
        if (!$ismultipart) {
            if (static::ENCODING_7BIT !== $this->Encoding) {
                $result .= $this->headerLine('Content-Transfer-Encoding', $this->Encoding);
            }
        }
        
        return $result;
    }

    public function createBody()
    {
        $body = '';
        $this->uniqueid = $this->generateId();
        $this->boundary[1] = 'b1_' . $this->uniqueid;
        $this->boundary[2] = 'b2_' . $this->uniqueid;
        $this->boundary[3] = 'b3_' . $this->uniqueid;
        
        if ($this->message_type === 'plain') {
            $body .= $this->encodeString($this->Body, $this->Encoding);
        } else {
            $body .= $this->getBoundary($this->boundary[1], $this->CharSet, '', $this->Encoding);
            $body .= $this->encodeString($this->AltBody ?: strip_tags($this->Body), $this->Encoding);
            $body .= static::$LE;
            $body .= $this->getBoundary($this->boundary[1], $this->CharSet, 'text/html', $this->Encoding);
            $body .= $this->encodeString($this->Body, $this->Encoding);
            $body .= static::$LE;
            $body .= $this->endBoundary($this->boundary[1]);
        }
        
        return $body;
    }

    protected function getBoundary($boundary, $charSet, $contentType, $encoding)
    {
        $result = '';
        if ('' === $charSet) {
            $charSet = $this->CharSet;
        }
        if ('' === $contentType) {
            $contentType = $this->ContentType;
        }
        if ('' === $encoding) {
            $encoding = $this->Encoding;
        }
        
        $result .= static::$LE . '--' . $boundary . static::$LE;
        $result .= sprintf('Content-Type: %s; charset=%s', $contentType, $charSet) . static::$LE;
        if (static::ENCODING_7BIT !== $encoding) {
            $result .= $this->headerLine('Content-Transfer-Encoding', $encoding);
        }
        $result .= static::$LE;
        
        return $result;
    }

    protected function endBoundary($boundary)
    {
        return static::$LE . '--' . $boundary . '--' . static::$LE;
    }

    protected function headerLine($name, $value)
    {
        return $name . ': ' . $value . static::$LE;
    }

    protected function textLine($value)
    {
        return $value . static::$LE;
    }

    public function addrFormat($addr)
    {
        if (empty($addr[2])) {
            return $this->secureHeader($addr[1]);
        }
        return $this->encodeHeader($this->secureHeader($addr[2]), 'phrase') . ' <' . $this->secureHeader($addr[1]) . '>';
    }

    public function encodeHeader($str, $position = 'text')
    {
        $matchcount = 0;
        switch (strtolower($position)) {
            case 'phrase':
                if (!preg_match('/[\200-\377]/', $str)) {
                    $encoded = addcslashes($str, "\0..\37\177\\\"");
                    if ($str !== $encoded || preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $str)) {
                        return '"' . $encoded . '"';
                    }
                    return $str;
                }
                $matchcount = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
                break;
            case 'comment':
                $matchcount = preg_match_all('/[()"]/', $str, $matches);
            case 'text':
            default:
                $matchcount += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
                break;
        }
        
        if ($matchcount > 0) {
            $maxlen = static::MAX_LINE_LENGTH - 8 - strlen($this->CharSet);
            if ($matchcount > strlen($str) / 3) {
                $encoding = 'B';
            } else {
                $encoding = 'Q';
            }
            if ('Q' === $encoding) {
                $encoded = $this->encodeQ($str, $position);
            } else {
                $encoded = $this->encodeB($str, $maxlen);
            }
            $encoded = '=?' . $this->CharSet . '?' . $encoding . '?' . $encoded . '?=';
            return $encoded;
        }
        return $str;
    }

    public function encodeQ($str, $position = 'text')
    {
        $pattern = '';
        switch (strtolower($position)) {
            case 'phrase':
                $pattern = '^A-Za-z0-9!*+\/ -';
                break;
            case 'comment':
                $pattern = '\(\)"';
            case 'text':
            default:
                $pattern .= '\000-\011\013\014\016-\037\075\077\137\177-\377' . $pattern;
                break;
        }
        $matches = [];
        if (preg_match_all('/[' . $pattern . ']/', $str, $matches)) {
            $s = implode('', $matches[0]);
            $s = preg_replace_callback('/(.)/S', function($m) {
                return sprintf('=%02X', ord($m[1]));
            }, $s);
            $str = strtr($str, [implode('', $matches[0]) => $s]);
        }
        return str_replace(' ', '_', $str);
    }

    public function encodeB($str, $maxlen = 0)
    {
        return base64_encode($str);
    }

    public function secureHeader($str)
    {
        return trim(str_replace(["\r", "\n"], '', $str));
    }

    public function encodeString($str, $encoding = self::ENCODING_BASE64)
    {
        $encoded = '';
        switch (strtolower($encoding)) {
            case static::ENCODING_BASE64:
                $encoded = chunk_split(base64_encode($str), static::STD_LINE_LENGTH, static::$LE);
                break;
            case static::ENCODING_7BIT:
            case static::ENCODING_8BIT:
                $encoded = static::normalizeBreaks($str);
                if (substr($encoded, -(strlen(static::$LE))) !== static::$LE) {
                    $encoded .= static::$LE;
                }
                break;
            case static::ENCODING_BINARY:
                $encoded = $str;
                break;
            case static::ENCODING_QUOTED_PRINTABLE:
                $encoded = $this->encodeQP($str);
                break;
            default:
                $this->setError(sprintf($this->lang('encoding'), $encoding));
                break;
        }
        return $encoded;
    }

    public function encodeQP($string)
    {
        return static::normalizeBreaks(quoted_printable_encode($string));
    }

    public static function normalizeBreaks($text, $breaktype = null)
    {
        if (null === $breaktype) {
            $breaktype = static::$LE;
        }
        return preg_replace('/\r\n?|\n/ms', $breaktype, $text);
    }

    protected function generateId()
    {
        $len = 32;
        if (function_exists('random_bytes')) {
            $bytes = random_bytes($len);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($len);
        } else {
            $bytes = '';
            for ($i = 0; $i < $len; ++$i) {
                $bytes .= chr(random_int(0, 255));
            }
        }
        return bin2hex($bytes);
    }

    protected function createMessageID()
    {
        $suffix = '';
        if (!empty($_SERVER['SERVER_NAME'])) {
            $suffix = $_SERVER['SERVER_NAME'];
        } else {
            $suffix = 'localhost.localdomain';
        }
        return sprintf('<%s@%s>', $this->uniqueid ?: $this->generateId(), $suffix);
    }

    public static function rfcDate()
    {
        date_default_timezone_set(@date_default_timezone_get());
        return date('D, j M Y H:i:s O');
    }

    public function alternativeExists()
    {
        return !empty($this->AltBody);
    }

    public function attachmentExists()
    {
        foreach ($this->attachment as $attachment) {
            if ('attachment' === $attachment[6]) {
                return true;
            }
        }
        return false;
    }

    public function inlineImageExists()
    {
        foreach ($this->attachment as $attachment) {
            if ('inline' === $attachment[6]) {
                return true;
            }
        }
        return false;
    }

    protected function doCallback($isSent, $to, $cc, $bcc, $subject, $body, $from, $extra)
    {
        if (!empty($this->action_function) && is_callable($this->action_function)) {
            call_user_func($this->action_function, $isSent, $to, $cc, $bcc, $subject, $body, $from, $extra);
        }
    }

    protected function edebug($str)
    {
        if ($this->SMTPDebug <= 0) {
            return;
        }
        
        if ($this->Debugoutput instanceof \Closure) {
            call_user_func($this->Debugoutput, $str, $this->SMTPDebug);
            return;
        }
        
        switch ($this->Debugoutput) {
            case 'error_log':
                error_log($str);
                break;
            case 'html':
                echo htmlentities(preg_replace('/[\r\n]+/', '', $str), ENT_QUOTES, 'UTF-8') . "<br>\n";
                break;
            case 'echo':
            default:
                $str = preg_replace('/\r\n|\r/', "\n", $str);
                echo gmdate('Y-m-d H:i:s'), "\t", trim(str_replace("\n", "\n                   \t                  ", trim($str))), "\n";
        }
    }

    protected function base64EncodeWrapMB($str, $linebreak = null)
    {
        $start = '=?' . $this->CharSet . '?B?';
        $end = '?=';
        $encoded = '';
        if (null === $linebreak) {
            $linebreak = static::$LE;
        }
        $mb_length = mb_strlen($str, $this->CharSet);
        $length = 75 - strlen($start) - strlen($end);
        $ratio = $mb_length / strlen($str);
        $avgLength = floor($length * $ratio * .75);
        for ($i = 0; $i < $mb_length; $i += $offset) {
            $lookBack = 0;
            do {
                $offset = $avgLength - $lookBack;
                $chunk = mb_substr($str, $i, $offset, $this->CharSet);
                $chunk = base64_encode($chunk);
                ++$lookBack;
            } while (strlen($chunk) > $length);
            $encoded .= $chunk . $linebreak;
        }
        $encoded = substr($encoded, 0, -strlen($linebreak));
        return $encoded;
    }

    public static $LE = "\r\n";
}
