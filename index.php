<?php
function action_wpcf7_before_send_mail($wpcf7) {

	$submission = WPCF7_Submission::get_instance();
	$form = WPCF7_ContactForm::get_current();
	$props = $form->get_properties();
	$id = $wpcf7->id();

	$posted_data = $submission->get_posted_data();

	$phone = '';
	$email = '';
	$subdomain='test';
	$status_id = '';
  // функция отправки каталога продукции
	require_once 'phpmailer/PHPMailerAutoload.php';

	function sendMessageWithFiles($to, $subject, $message, $files = []){
	    $mail = new PHPMailer;
	    $mail->isSMTP();
	    $mail->Host = "smtp.yandex.ru";
	    $mail->SMTPAuth = true;
	    $mail->Username = "";
	    $mail->Password = "---------";
	    $mail->SMTPSecure = "ssl";
	    $mail->Port = 465;

		$mail->setFrom('noreply@site.ru', 'Сайт');
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
	$email = false;
	// if (t('email') && preg_match('/^[a-z0-9_\-\.]{1,}@[a-z0-9\-\.]{1,}\.[a-z]{2,4}$/i', tr('email'))) {
	//     $message .= "E-mail: " . v('email');
	// 	$email = tr('email');
	// }
	// $message.="\n";
	sendMessageWithFiles($to, "Каталог продукции", "<html>
	  <head>
		<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">
	  </head>
	  <body bgcolor=\"#FFFFFF\" text=\"#000000\">
	    <p>Здравствуйте!</p>
	  </body>
	</html>", ["files/test.txt"]);
  // функция отправки каталога продукции

	if (preg_match('/^\+?[0-9\s\-\(\)]+$/i', trim($posted_data['your-phone']))){
		$phone = trim(htmlspecialchars($posted_data['your-phone'], ENT_QUOTES));
	  $status_id = 123;
	}

	if (preg_match('/^[a-z0-9_\-\.]{1,}@[a-z0-9\-\.]{1,}\.[a-z]{2,4}$/i', trim($posted_data['your-phone']))) {
	    $email = trim(htmlspecialchars($posted_data['your-phone'], ENT_QUOTES));
	    $status_id = 123;
	}

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

	$Response=json_decode($out,true);
	$account=$Response['response']['account'];

	$amoConactsFields = $account['custom_fields']['contacts'];
	$sFields = array_flip(array(
			'PHONE', //Телефон. Варианты: WORK, WORKDD, MOB, FAX, HOME, OTHER
			'EMAIL' //Email. Варианты: WORK, PRIV, OTHER
		)
	);

	foreach($amoConactsFields as $afield) {
		if(isset($sFields[$afield['code']])) {
			$sFields[$afield['code']] = $afield['id'];
		}
	}

	$user=array(
	  'USER_LOGIN'=>'test@yandex.ru',
	  'USER_HASH'=>'' //в профиле пользователя
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

	$out=curl_exec($curl);
	$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl);
	$Response=json_decode($out,true);
	$Response = $Response['response'];
	if(isset($Response['auth'])) {
	    $leads['request']['leads']['add'] = array(
	        array(
	          'name'=>'Заказ каталога #'. date('YmdHis'),
	          'status_id'=>$status_id,// $status_id
	          "responsible_user_id"=>  // получаем из массива $account
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
	    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
	    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

	    $out=curl_exec($curl);
	    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	    $Response=json_decode($out,true);

	    if(is_array($Response['response']['leads']['add'])) {
	    	foreach($Response['response']['leads']['add'] as $lead) {
	    		$lead_id = $lead["id"]; //id новой сделки
	    	};
	    }

	    //ДОБАВЛЕНИЕ КОНТАКТА
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
	    #Формируем ссылку для запроса
	    $link='https://'.$subdomain.'.amocrm.ru/private/api/v2/json/contacts/set';
	    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
	    #Устанавливаем необходимые опции для сеанса cURL
	    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
	    curl_setopt($curl,CURLOPT_URL,$link);
	    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
	    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($set));
	    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
	    curl_setopt($curl,CURLOPT_HEADER,false);
	    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
	    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
	    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	    // CheckCurlResponse($code);
	    // $Response=json_decode($out,true);
			$wpcf->skip_mail = true;
	} else {
		$wpcf->skip_mail = false;
	}

	return $form;


}
add_action("wpcf7_before_send_mail", "action_wpcf7_before_send_mail");
