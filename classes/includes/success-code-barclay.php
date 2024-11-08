<?php
if (!defined('ABSPATH')) {
	exit;
}

class Ambpg_Epdq_SuccessCodes
{
	/**
	 * @var string[]
	 */
	private static $paymentStatusCodes = [
		0 => 'Invalid or incomplete',
		1 => 'Cancelled by customer',
		2 => 'Authorisation declined',
		4 => 'Order stored',
		40 => 'Stored waiting external result',
		41 => 'Waiting for client payment',
		46 => 'Waiting authentication',
		5 => 'Authorised',
		50 => 'Authorized waiting external result',
		51 => 'Authorisation waiting',
		52 => 'Authorisation not known',
		55 => 'Standby',
		56 => 'OK with scheduled payments',
		57 => 'Not OK with scheduled payments',
		59 => 'Authoris. to be requested manually',
		6 => 'Authorised and cancelled',
		61 => 'Author. deletion waiting',
		62 => 'Author. deletion uncertain',
		63 => 'Author. deletion refused',
		64 => 'Authorised and cancelled',
		7 => 'Payment deleted',
		71 => 'Payment deletion pending',
		72 => 'Payment deletion uncertain',
		73 => 'Payment deletion refused',
		74 => 'Payment deleted',
		75 => 'Deletion handled by merchant',
		8 => 'Refund',
		81 => 'Refund pending',
		82 => 'Refund uncertain',
		83 => 'Refund refused',
		84 => 'Refund',
		85 => 'Refund handled by merchant',
		9 => 'Payment requested',
		91 => 'Payment processing',
		92 => 'Payment uncertain',
		93 => 'Payment refused',
		94 => 'Refund declined by the acquirer',
		95 => 'Payment handled by merchant',
		96 => 'Refund reversed',
		99 => 'Being processed'
	];

	/**
	 * @param $code
	 *
	 * @return string
	 */
	public static function ambpg_getMessage($code): ?string
	{
		if ($code == '') {
			return null;
		}

		return self::$paymentStatusCodes[$code] ?? 'Unknown Error Code';
	}
}