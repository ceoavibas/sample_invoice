<?php  //if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Utiles
{

	// public function leterNumber($n = ''){
	// 	$letra = array(
	// 		0		=> '',
	// 		1		=> 'UNO',
	// 		2		=> 'DOS',
	// 		3		=> 'TRES',
	// 		4		=> 'CUATRO',
	// 		5		=> 'CINCO',
	// 		6		=> 'SEIS',
	// 		7		=> 'SIETE',
	// 		8		=> 'OCHO',
	// 		9		=> 'NUEVE',
	// 		10		=> 'DIEZ',
	// 		11		=> 'ONCE',
	// 		12		=> 'DOCE',
	// 		13		=> 'TRECE',
	// 		14		=> 'CATORCE',
	// 		15		=> 'QUINCE',
	// 		16		=> 'DIECISEIS',
	// 		17		=> 'DIECISITE',
	// 		18		=> 'DIECIOCHO',
	// 		19		=> 'DIECINUEVE',
	// 		20		=> 'VEINTE',
	// 		30		=> 'TREINTA',
	// 		40		=> 'CUARENTA',
	// 		50		=> 'CINCUENTA',
	// 		60		=> 'SESENTA',
	// 		70		=> 'SETENTA',
	// 		80		=> 'OCHENTA',
	// 		90		=> 'NOVENTA',
	// 		100		=> 'CIEN',
	// 		200		=> 'DOSCIENTOS',
	// 		300		=> 'TRESCIENTOS',
	// 		400		=> 'CUATROCIENTOS',
	// 		500		=> 'QUINIENTOS',
	// 		600		=> 'SEISCIENTOS',
	// 		700		=> 'SETECIENTOS',
	// 		800		=> 'OCHOCIENTOS',
	// 		900		=> 'NOVECIENTOS',
	// 		1000	=> 'MIL'
	// 		);
	// 	$n = (int)$n;
	// 	if($n <= 20)
	// 		return $letra[$n];
	// 	if($n <= 100){
	// 		$a = (int)($n/10);
	// 		$b = $n%10;
	// 		return ($a == 2 ?  substr($letra[$a*10], 0, 5).'I' : $letra[$a*10]).($b == 0 ? '' : ($a == 2 ? $letra[$b] : ' Y '.$letra[$b]));
	// 	}
	// 	if($n > 100 && $n < 1000){
	// 		$a = (int)($n/100);
	// 		$b = (int)($n - $a*100)/10;
	// 		$c = (int)($b*10)%10;
	// 		$d = (int)$n - (int)$a*100;
	// 		return $letra[(int)$a*100].($a == 1 ? 'TO ' : '').((int)$d < 20 ? ' '.$this->leterNumber($d) : ' '.$letra[(int)$b*10].' Y '.$letra[$c]);
	// 	}
	// 	if($n >= 1000 && $n < 1000000){
	// 		$a = (int)($n/1000);
	// 		if($a == 1)
	// 			return 'UN MIL '.$this->leterNumber($n - 1000);
	// 		if($a > 1)
	// 			return $this->leterNumber($a).' MIL '.$this->leterNumber($n - $a * 1000);
	// 	}
	// 	if($n >= 1000000){
	// 		$a = (int)($n/1000000);
	// 		if($a == 1)
	// 			return 'UN MILLON '.$this->leterNumber($n - 1000000);
	// 		if($a > 1)
	// 			return $this->leterNumber($a).' MILLONES '.$this->leterNumber($n - $a * 1000000);
	// 	}
	// }

	// public function getValues($cod_doc = '03',$num_serie = '',$num_documento = ''){
	// 	$url         = "http://localhost:8093/WebDoc.asmx?WSDL"; 
	// 	$client     = new SoapClient($url); 
	// 	$data = array();
	// 	if($cod_doc == '03'){
	// 		$r = $client->GetBoletaX(array(
	// 			'serie'				=> $num_serie,
	// 			'correlativo'		=> $num_documento
	// 			));
	// 		$b = explode(' ||| ', $r->GetBoletaXResult);
	// 		$data['DigestValue']	= $b[0];
	// 		$data['SignatureValue']	= $b[1];
	// 	}
	// 	else{
	// 		$r = $client->GetFacturaX(array(
	// 			'serie'				=> $num_serie,
	// 			'correlativo'		=> $num_documento
	// 			));
	// 		$b = explode(' ||| ', $r->GetFacturaXResult);
	// 		$data['DigestValue']	= $b[0];
	// 		$data['SignatureValue']	= $b[1];
	// 	}
	// 	return $data;
	// 	//return array('DigestValue'=>'firma','SignatureValue'=>'huella');
	// }


	private static $UNIDADES = [
        '',
        'UNO ',
        'DOS ',
        'TRES ',
        'CUATRO ',
        'CINCO ',
        'SEIS ',
        'SIETE ',
        'OCHO ',
        'NUEVE ',
        'DIEZ ',
        'ONCE ',
        'DOCE ',
        'TRECE ',
        'CATORCE ',
        'QUINCE ',
        'DIECISEIS ',
        'DIECISIETE ',
        'DIECIOCHO ',
        'DIECINUEVE ',
        'VEINTE '
    ];
    private static $DECENAS = [
        'VENTI',
        'TREINTA ',
        'CUARENTA ',
        'CINCUENTA ',
        'SESENTA ',
        'SETENTA ',
        'OCHENTA ',
        'NOVENTA ',
        'CIEN '
    ];
    private static $CENTENAS = [
        'CIENTO ',
        'DOSCIENTOS ',
        'TRESCIENTOS ',
        'CUATROCIENTOS ',
        'QUINIENTOS ',
        'SEISCIENTOS ',
        'SETECIENTOS ',
        'OCHOCIENTOS ',
        'NOVECIENTOS '
    ];
    public static function leterNumber($number, $moneda = '', $centimos = '', $forzarCentimos = false)
    {
        $converted = '';
        $decimales = '';
        if (($number < 0) || ($number > 999999999)) {
            return 'No es posible convertir el numero a letras';
        }
        $div_decimales = explode('.',$number);
        if(count($div_decimales) > 1){
            $number = $div_decimales[0];
            $decNumberStr = (string) $div_decimales[1];
            if(strlen($decNumberStr) == 2){
                $decNumberStrFill = str_pad($decNumberStr, 9, '0', STR_PAD_LEFT);
                $decCientos = substr($decNumberStrFill, 6);
                $decimales = self::convertGroup($decCientos);
            }
        }
        else if (count($div_decimales) == 1 && $forzarCentimos){
            $decimales = 'CERO ';
        }
        $numberStr = (string) $number;
        $numberStrFill = str_pad($numberStr, 9, '0', STR_PAD_LEFT);
        $millones = substr($numberStrFill, 0, 3);
        $miles = substr($numberStrFill, 3, 3);
        $cientos = substr($numberStrFill, 6);
        if (intval($millones) > 0) {
            if ($millones == '001') {
                $converted .= 'UN MILLON ';
            } else if (intval($millones) > 0) {
                $converted .= sprintf('%sMILLONES ', self::convertGroup($millones));
            }
        }
        if (intval($miles) > 0) {
            if ($miles == '001') {
                $converted .= 'UN MIL ';
            } else if (intval($miles) > 0) {
                $converted .= sprintf('%sMIL ', self::convertGroup($miles));
            }
        }
        if (intval($cientos) > 0) {
            if ($cientos == '001') {
                $converted .= 'UN ';
            } else if (intval($cientos) > 0) {
                $converted .= sprintf('%s ', self::convertGroup($cientos));
            }
        }
        
        // if(empty($decimales)){
        //     $valor_convertido = $converted . strtoupper($moneda);
        // } else {
        //     // $valor_convertido = $converted . strtoupper($moneda) . ' Y ' . $decimales . ' ' . strtoupper($centimos);
            
            
        // }
        $valor_convertido = $converted . strtoupper($moneda) . ' Y ' . $div_decimales[1].'/100 SOLES';
        return $valor_convertido;
    }
    public static function convertGroup($n)
    {
        $output = '';
        if ($n == '100') {
            $output = "CIEN ";
        } else if ($n[0] !== '0') {
            $output = self::$CENTENAS[$n[0] - 1];
        }
        $k = intval(substr($n,1));
        if ($k <= 20) {
            $output .= self::$UNIDADES[$k];
        } else {
            if(($k > 30) && ($n[2] !== '0')) {
                $output .= sprintf('%sY %s', self::$DECENAS[intval($n[1]) - 2], self::$UNIDADES[intval($n[2])]);
            } else {
                $output .= sprintf('%s%s', self::$DECENAS[intval($n[1]) - 2], self::$UNIDADES[intval($n[2])]);
            }
        }
        return $output;
    }



	public function getDateSpanish($date_emision){
		
		$months = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Setiembre","Octubre","Noviembre","Diciembre");

		$date = explode(" ", $date_emision)[0];
		$date = explode("-", $date);

		$year = $date[0];
		$month = $date[1];
		$day = $date[2];


		// return date('d'). ' de '. $months[date('n') - 1]. ' del '. date('Y');
		return $day. ' de '. $months[$month - 1]. ' del '. $year;
	}
}
