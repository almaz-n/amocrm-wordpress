<?php
function setLog ($request = [], $errmsg, $id_form) {
  $file = dirname(__FILE__).'/amocrm_log.txt';
  $date = date('Y-m-d H:i:s');
  //формируем новую строку в логе
  $err_str = 'Время: '.$date.'.||';
  $err_str .= 'Отправлена с формы: '.$id_form.'.||';
	if (count($request)) {
		$err_str .= 'Данные введенные пользователем: '.$request['0'].'. ||';
	} else {
		$err_str .= 'Пользователь не ввел данных';
	}

  $err_str .= 'Сообщения об ошибки amoCRM: '.$errmsg."\n";

	file_put_contents($file, $err_str, FILE_APPEND);
}


// define the wpcf7_mail_sent callback
function action_wpcf7_mail_sent( $wpcf7 ) {

	$submission = WPCF7_Submission::get_instance();
	$form = WPCF7_ContactForm::get_current();
	$props = $form->get_properties();
	$id = $wpcf7->id(); // id формы
	$posted_data = $submission->get_posted_data();

	if ($id == 167) { // создание лида только с определенной формы на сайте
		$phone = '';
		$email = '';
		$subdomain='test';
		$status_id = '';

		$error = '';
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
		);

		function sendMessageWithFiles($to, $subject, $message, $files = []){
			require_once 'phpmailerfddWEg/PHPMailerAutoload.php';
				$mail = new PHPMailer;
				$mail->isSMTP();
				$mail->Host = "smtp.yandex.ru";
				$mail->SMTPAuth = true;
				$mail->Username = "mail@site.ru";
				$mail->Password = "password";
				$mail->SMTPSecure = "ssl";
				$mail->Port = 465;

			$mail->setFrom('mail@site.ru', 'Сайт');
				$to = preg_split("/,\s*/", $to);
				foreach ($to as $t) {
						$mail->addAddress($t);
				}
				$mail->CharSet = 'utf-8';
			if (count($files)) {
				foreach ($files as $file) {
					$mail->addAttachment($file);
				}
			}
			$mail->isHTML(true);
				$mail->Subject = $subject;
				$mail->Body = $message;
			$mail->AltBody = strip_tags($message);
			$mail->send();

		}

		if (preg_match('/^\+?[0-9\s\-\(\)]+$/i', trim($posted_data['your-phone']))){
			$phone = trim(htmlspecialchars($posted_data['your-phone'], ENT_QUOTES));
			$status_id = 17555533; // получаем из метода 'https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		}

		if (preg_match('/^[a-z0-9_\-\.]{1,}@[a-z0-9\-\.]{1,}\.[a-z]{2,20}$/i', trim($posted_data['your-phone']))) {
				$email = trim(htmlspecialchars($posted_data['your-phone'], ENT_QUOTES));
				$status_id = 17555530;
		}


		// АвторизациЯ
		$user=array(
			'USER_LOGIN'=>'test@yandex.ru', //данные для входа в amocrm
			'USER_HASH'=>'' // в личном кабинете в профиле пользователя
		);


		$link='https://'.$subdomain.'.amocrm.ru/private/api/auth.php?type=json';
		$curl=curl_init();

		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
		curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($user));
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt');
		curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt');
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

		$out = curl_exec($curl);
		$code = curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code=(int)$code;

		try	{
		 if($code!=200 && $code!=204) {
		    throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		  }
		}
		catch(Exception $E)	{
		 $error .= 'Ошибка auth: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode();
		}

		$Response = json_decode($out,true);
		$authResponse = $Response['response'];
		//АвторизациЯ


		//Данные аккаунтА
		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/accounts/current';
		$curl=curl_init();
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt');
		curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt');
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

		$out=curl_exec($curl);
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);

		$code=(int)$code;

		try	{
		 if($code!=200 && $code!=204) {
		    throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		  }
		}
		catch(Exception $E)	{
		 $error = 'Ошибка accounts: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode();
		}

		$Response=json_decode($out,true);
		$account=$Response['response']['account'];

		$amoConactsFields = $account['custom_fields']['contacts'];
		$sFields = array_flip(array(
				'PHONE',
				'EMAIL'
			)
		);

		foreach($amoConactsFields as $afield) {
			if(isset($sFields[$afield['code']])) {
				$sFields[$afield['code']] = $afield['id'];
			}
		}


	if(isset($authResponse['auth'])) {
		$leads['request']['leads']['add'] = array(
				array(
					'name'=>'Заказ каталога #'. date('YmdHis'),
					'status_id'=>$status_id,
					"responsible_user_id"=> 900525
				)
		);

		$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/leads/set';

		$curl=curl_init();

		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
		curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($leads));
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt');
		curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt');
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

		$out=curl_exec($curl);
		$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
		$code=(int)$code;

		try	{
		 if($code!=200 && $code!=204) {
		    throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		  }
		}
		catch(Exception $E)	{
		 $error .= 'Ошибка leads: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode();
		}

		$Response=json_decode($out,true);

		if(is_array($Response['response']['leads']['add'])) {
			foreach($Response['response']['leads']['add'] as $lead) {
				$lead_id = $lead["id"];
			};
		}
		$lead_id_message = substr($lead_id, 2);

		if($email != '') {
				sendMessageWithFiles($email, "Тема", "<html>
					<head>
					<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">
					</head>
					<body bgcolor=\"#FFFFFF\" text=\"#000000\">

					</body>
				</html>", ["files/asd.pdf"]);
			}

			$contact = array(
				'name' => 'Заказ каталога #'.date('YmdHis'),
				'linked_leads_id' => array($lead_id),
				'responsible_user_id' => 900525,
				'custom_fields'=>array(
					array(
						'id' => $sFields['PHONE'],
						'values' => array(
							array(
								'value' => $phone,
								'enum' => 'MOB'
							)
						)
					),
					array(
						'id' => $sFields['EMAIL'],
						'values' => array(
							array(
								'value' => $email,
								'enum' => 'WORK'
							)
						)
					)
				)
			);
			$set['request']['contacts']['add'][]=$contact;

			$link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/set';
			$curl=curl_init();
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
			curl_setopt($curl,CURLOPT_URL,$link);
			curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
			curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($set));
			curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
			curl_setopt($curl,CURLOPT_HEADER,false);
			curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(__FILE__).'/cookie.txt');
			curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(__FILE__).'/cookie.txt');
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
			$out=curl_exec($curl);
			$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);

			$code=(int)$code;

			try	{
			 if($code!=200 && $code!=204) {
					throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
				}
			}
			catch(Exception $E)	{
			 $error .= 'Ошибка contacts: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode();
			}

			setLog ([$email], $error, $id);  //отлавливаем ошибки и записываем в log
		}
	}
};

add_action( 'wpcf7_mail_sent', 'action_wpcf7_mail_sent', 10, 1 );
