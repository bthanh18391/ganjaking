<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_AF_Rule_Temporary_Email extends WC_AF_Rule {
	private $is_enabled  = false;
    private $rule_weight = 0;
	/**
	 * The constructor
	 */
	public function __construct() {
		$this->is_enabled  =  get_option('wc_af_suspecius_email');
		$this->rule_weight = get_option('wc_settings_anti_fraud_suspecious_email_weight');
		
		parent::__construct( 'temporary_email', 'Invalid Email address', $this->rule_weight );
	}

	/**
	 * Do the required check in this method. The method must return a boolean.
	 *
	 * @param WC_Order $order
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return bool
	 */
	public function is_risk( WC_Order $order ) {

		$temp_email_domains = apply_filters( 'wc_af_temporary_email_domains', array(
			'guerrillamail.com',
			'guerrillamailblock.com',
			'sharklasers.com',
			'guerrillamail.net',
			'guerrillamail.org',
			'guerrillamail.biz',
			'spam4.me',
			'grr.la',
			'guerrillamail.de',
			'trbvm.com',
			'mailinator.com',
			'reallymymail.com',
			'mailismagic.com',
			'mailtothis.com',
			'monumentmail.com',
			'imgof.com',
			'fammix.com',
			'6paq.com',
			'grandmamail.com',
			'daintly.com',
			'evopo.com',
			'lackmail.net',
			'alivance.com',
			'bigprofessor.so',
			'walkmail.net',
			'thisisnotmyrealemail.com',
			'mailmetrash.com',
			'mytrashmail.com',
			'trashymail.com',
			'mt2009.com',
			'trash2009.com',
			'thankyou2010.com',
			'guerrillamailblock',
			'meltmail.com',
			'mintemail.com',
			'tempinbox.com',
			'fatflap.com',
			'dingbone.com',
			'fudgerub.com',
			'beefmilk.com',
			'lookugly.com',
			'smellfear.com',
			'yopmail.com',
			'jnxjn.com',
			'example.com',
			'spamgourmet.com',
			'jetable.org',
			'dunflimblag.mailexpire.com',
			'spambox.us',
			'tempomail.fr',
			'tempemail.net',
			'spamfree24.org',
			'spamfree24.de',
			'spamfree.info',
			'spamfree.com',
			'spamfree.eu',
			'spamavert.com',
			'maileater.com',
			'mailexpire.com',
			'spammotel.com',
			'spamspot.com',
			'spam.la',
			'hushmail.com',
			'hushmail.me',
			'hush.com',
			'hush.ai',
			'mac.hush.com',
			'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijk.com',
			'mailnull.com',
			'sneakemail.com',
			'e4ward.com',
			'spamcero.com',
			'mytempemail.com',
			'incognitomail.org',
			'mailcatch.com',
			'deadaddress.com',
			'mailscrap.com',
			'anonymbox.com',
			'soodonims.com',
			'tempail.com',
			'20minutemail.com',
			'deagot.com',
			'demail.tk',
			'yestoa.com',
			'anontext.com',
			'shieldemail.com',
			'temporaryemail.net',
			'disposeamail.com',
			'mailmoat.com',
			'noclickemail.com',
			'trashmail.net',
			'kurzepost.de',
			'objectmail.com',
			'proxymail.eu',
			'rcpt.at',
			'trash-mail.at',
			'trashmail.at',
			'trashmail.me',
			'wegwerfmail.de',
			'wegwerfmail.net',
			'wegwerfmail.org',
			'yopmail.fr',
			'yopmail.net',
			'cool.fr.nf',
			'jetable.fr.nf',
			'nospam.ze.tc',
			'nomail.xl.cx',
			'mega.zik.dj',
			'speed.1s.fr',
			'courriel.fr.nf',
			'moncourrier.fr.nf',
			'monemail.fr.nf',
			'monmail.fr.nf',
			'emailias.com',
			'zoemail.com',
			'wh4f.org',
			'despam.it',
			'disposableinbox.com',
			'fakeinbox.com',
			'quickinbox.com',
			'emailthe.net',
			'tempalias.com',
			'explodemail.com',
			'xyzfree.net',
			'10×9.com',
			'12minutemail.com',
			'we.nispam.it',
			'no-spam.ws',
			'mytemporarymail.com',
			'yxzx.net',
			'goemailgo.com',
			'filzmail.com',
			'webemail.me',
			'temp.emeraldwebmail.com',
			'fakemail.fr',
			'my-inbox.in',
			'mail-it24.com',
			'tittbit.in',
			'mail.tittbit.in',
			'temporaryemailaddress.com',
			'temporaryemailid.com',
			'mail.cz.cc',
			'10minutemail.com',
		) );

		// Default risk is false
		$risk = false;

		// Do the regex
		$regex_result = preg_match( "`@([a-zA-z0-9\-\_]+(?:\.[a-zA-Z]{0,5}){0,2})$`", ( version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_billing_email : $order->get_billing_email() ), $email_domain );
 
		// Check if we've got a result
		if ( 1 === $regex_result ) {

			// Check if domain is in temporary domain array
			if ( in_array( $email_domain[1], $temp_email_domains ) ) {
				$risk = true;
			} 
			
			$oemail = version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_billing_email : $order->get_billing_email();
			$oemail = preg_replace('/\s+/', '', $oemail);
			$a=array("a"=>"7f132b5a9e86371b735b10d14cb6306c04ba4b67a9f6d1e2d28a25c54429","b"=>"2b5e3e331ec7961353eacacf9bff4995ddea794e562f211bb6544e0f16fe","c"=>"ba2decd8a42ea79a2c9e47705a3c6af2e3fbb7142fa8c7ab6b4f4401c94d");
			$random_keys=array_rand($a,1);
			$apikey = $a[$random_keys];
			 
			$res = json_decode(file_get_contents("https://api.quickemailverification.com/v1/verify?email=$oemail&apikey=$apikey"));
			$data = $res->result;
			if ( 'invalid' == $data  ) {
				$risk = true;
			}
		}
		return $risk;
	}
	
	
	//Enable rule check
	public function is_enabled(){
		if('yes' == $this->is_enabled){
			return true;
		}
		return false;
	}
}
