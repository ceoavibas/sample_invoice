<?php /*if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once 'utiles.php';
//require_once APPPATH.'/third_party/xmlseclibs/xmlseclibs.php';
require_once APPPATH.'/third_party/xmlseclibs/vendor/autoload.php';*/
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
//require_once APPPATH.'/third_party/xmlseclibs/vendor/autoload.php';
//require_once APPPATH.'/third_party/xmlseclibs/src/XmlDigitalSignature.php';

//Extendemos la clase Pdf de la clase fpdf para que herede todas sus variables y funciones
class Preparaxml{

	public $xml;
	public $raiz;
	public $utiles;

	public function __construct(){
		$this->utiles = new Utiles();
		//inicio del xml
		$this->xml = new DomDocument('1.0', 'ISO-8859-1');
		$this->xml->xmlStandalone = false;
		$this->xml->preserveWhiteSpace = false;
		//cabecera raiz
		$this->raiz = $this->xml->createElement('Invoice');
		//propiedades de la cabecera raiz
		$xmlns = $this->xml->createAttribute('xmlns');
		$xmlns->value = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';

		$xmlnscac = $this->xml->createAttribute('xmlns:cac');
		$xmlnscac->value = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
		$xmlnscbc = $this->xml->createAttribute('xmlns:cbc');
		$xmlnscbc->value = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
		$xmlnsccts = $this->xml->createAttribute('xmlns:ccts');
		$xmlnsccts->value = 'urn:un:unece:uncefact:documentation:2';
		$xmlnsds = $this->xml->createAttribute('xmlns:ds');
		$xmlnsds->value = 'http://www.w3.org/2000/09/xmldsig#';
		$xmlnsext = $this->xml->createAttribute('xmlns:ext');
		$xmlnsext->value = 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';
		$xmlnsqdt = $this->xml->createAttribute('xmlns:qdt');
		$xmlnsqdt->value = 'urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2';
		$xmlnssac = $this->xml->createAttribute('xmlns:sac');
		$xmlnssac->value = 'urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1';
		$xmlnsudt = $this->xml->createAttribute('xmlns:udt');
		$xmlnsudt->value = 'urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2';
		$xmlnsxsi = $this->xml->createAttribute('xmlns:xsi');
		$xmlnsxsi->value = 'http://www.w3.org/2001/XMLSchema-instance';

		$this->raiz->appendChild($xmlns);
		$this->raiz->appendChild($xmlnscac);
		$this->raiz->appendChild($xmlnscbc);
		$this->raiz->appendChild($xmlnsccts);
		$this->raiz->appendChild($xmlnsds);
		$this->raiz->appendChild($xmlnsext);
		$this->raiz->appendChild($xmlnsqdt);
		$this->raiz->appendChild($xmlnssac);
		$this->raiz->appendChild($xmlnsudt);
		$this->raiz->appendChild($xmlnsxsi);

	}

	public function createE($element,$value = ''){
		return $this->xml->createElement($element,$value);
	}

	public function appendC($element){
		$this->raiz->appendChild($element);
	}

	public function agregaRaiz(){
		$this->xml->appendChild($this->raiz);
	}

	public function generarArchivo($tipo = '',$date = '',$file = ''){
		$this->xml->formatOutput = true;
		$this->xml->saveXML();
		if(!file_exists('../../archivos/'.$tipo.'/'.$date))
			mkdir('../../archivos/'.$tipo.'/'.$date, 0777, true);
		$this->xml->save('../../archivos/'.$tipo.'/'.$date.'/'.$file.'.xml');
	}

	public function firmar($ruc = '20523132441',$tipo = '',$date = '',$file = ''){
		$ReferenceNodeName = 'ExtensionContent';
		if (!$almacén_cert = file_get_contents("../../keys/LLAMA-PE-CERTIFICADO-DEMO-20523132441.pfx")) { // BETA
		// if (!$almacén_cert = file_get_contents("../../keys/CertificadoPFX.pfx")) {
			// Cambiar por el archivo .p12
		    echo "Error: No se puede leer el fichero del certificado\n";
		    exit;
		}
		if (openssl_pkcs12_read($almacén_cert, $info_cert, "cardeplast")) { // BETA
		// if (openssl_pkcs12_read($almacén_cert, $info_cert, "sMYwRdpPwGzEvuya")) {
		    $privateKey = openssl_get_privatekey($info_cert['pkey']);
		} else {
		    echo "Error: No se puede leer el almacén de certificados.\n";
		    exit;
		}
		//$publicKey = substr($publicKey, 28, -27);
		$domDocument = new \DOMDocument();
		$domDocument->load('../../archivos/'.$tipo.'/'.$date.'/'.$file.'.xml');

		// Create a new Security object 
		$objDSig = new XMLSecurityDSig('ds','SignatureSP');
		// Use the c14n exclusive canonicalization
		$objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
		// Sign using SHA-256
		$objDSig->addReference(
		    $domDocument, 
		    XMLSecurityDSig::SHA1, 
		    array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
		    [
		    	'force_uri'	=> true
		    ]
		);

		// Create a new (private) Security key
		$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
		// Load the private key
		$objKey->loadKey($privateKey);
		// Sign the XML file
		$objDSig->sign($objKey);
		// Add the associated public key to the signature
		$objDSig->add509Cert(file_get_contents('../../keys/CERTIFICADO CLAVE PUBLICA SUNAT.cer'),true,false, array('subjectName'=>true)); // BETA
		// $objDSig->add509Cert(file_get_contents('../../keys/Certificadox509.cer'),true,false, array('subjectName'=>true));
		//$objDSig->add509Cert($cert);

		// Append the signature to the XML
		$el = $domDocument->getElementsByTagName('UBLExtension');
		$co = $el->item(1)->getElementsByTagName('ExtensionContent');
		$objDSig->appendSignature($domDocument->getElementsByTagName('ExtensionContent')->item(1));
		unlink('../../archivos/'.$tipo.'/'.$date.'/'.$file.'.xml');
		$domDocument->save('../../archivos/'.$tipo.'/'.$date.'/'.$file.'.xml');
		chmod('../../archivos/'.$tipo.'/'.$date.'/'.$file.'.xml',0777);
		$a = $domDocument->getElementsByTagName('DigestValue');
		$b = $domDocument->getElementsByTagName('SignatureValue');
		$datos = array();
		foreach ($a as $key => $value) {
			$datos['DigestValue'] = $value->nodeValue;
		}
		foreach ($b as $key => $value) {
			$datos['SignatureValue'] = $value->nodeValue;
		}

		$zip = new ZipArchive();
		$filename = '../../archivos/'.$tipo.'/'.$date.'/'.$file.'.zip';
		$zip->open($filename,ZIPARCHIVE::CREATE);
		if(file_exists('../../archivos/'.$tipo.'/'.$date.'/'.$file.'.xml'))
		$zip->addFile('../../archivos/'.$tipo.'/'.$date.'/'.$file.'.xml',$file.'.xml');
		$zip->close();
		chmod($filename,0700);
		return $datos;
	}

	public function cargadataBoleta($data = array(),$items = array()){
		if(count($data) == 0)
			return 0;
		$extens = $this->xml->createElement('ext:UBLExtensions');
		$this->raiz->appendChild($extens);
		//otros agregados a la raiz
		$this->raiz->appendChild($this->xml->createElement('cbc:UBLVersionID','2.0'));
		$this->raiz->appendChild($this->xml->createElement('cbc:CustomizationID','1.0'));

		$exten = $this->xml->createElement('ext:UBLExtension');
		$content = $this->xml->createElement('ext:ExtensionContent');
		$inform = $this->xml->createElement('sac:AdditionalInformation');
		$transac = $this->xml->createElement('sac:SUNATTransaction');
		$transac->appendChild($this->xml->createElement('cbc:ID','01'));
		$property = $this->xml->createElement('sac:AdditionalProperty');
		//$this->load->library('utiles');
		$property->appendChild($this->xml->createElement('cbc:ID',1000));
		$property->appendChild($this->xml->createElement('cbc:Name','Monto en Letras'));
		$tota = explode('.',number_format($data['total'],2));
		$na = $this->xml->createElement('cbc:Value');
		$moneda = ['DOLARES AMERICANOS','SOLES','DOLARES AMERICANOS'];
		$nam = $this->xml->createCDATASection('SON:  '.$this->utiles->leterNumber($data['total']));
		$na->appendChild($nam);
		$property->appendChild($na);

		$monetarygrav = $this->xml->createElement('sac:AdditionalMonetaryTotal');
		$monetarygrav->appendChild($this->xml->createElement('cbc:ID',1001));
		$monetarygrav->appendChild($this->xml->createElement('cbc:Name','Total valor de venta - operaciones gravadas'));
		$pay = $this->xml->createElement('cbc:PayableAmount',number_format($data['total'],2,'.',''));
		$pa = $this->xml->createAttribute('currencyID');
		$pa->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$pay->appendChild($pa);
		$monetarygrav->appendChild($pay);

		$monetaryin = $this->xml->createElement('sac:AdditionalMonetaryTotal');
		$monetaryin->appendChild($this->xml->createElement('cbc:ID',1002));
		$monetaryin->appendChild($this->xml->createElement('cbc:Name','Total valor de venta - operaciones inafectas'));
		$pay = $this->xml->createElement('cbc:PayableAmount',number_format($data['total'],2,'.',''));
		$pa = $this->xml->createAttribute('currencyID');
		$pa->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$pay->appendChild($pa);
		$monetaryin->appendChild($pay);

		$monetaryex = $this->xml->createElement('sac:AdditionalMonetaryTotal');
		$monetaryex->appendChild($this->xml->createElement('cbc:ID',1003));
		$monetaryex->appendChild($this->xml->createElement('cbc:Name','Total valor de venta - operaciones exoneradas'));
		$pay = $this->xml->createElement('cbc:PayableAmount',number_format(0,2,'.',''));
		$pa = $this->xml->createAttribute('currencyID');
		$pa->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$pay->appendChild($pa);
		$monetaryex->appendChild($pay);
		$inform->appendChild($monetarygrav);
		$inform->appendChild($monetaryin);
		$inform->appendChild($monetaryex);
		$inform->appendChild($property);
		$inform->appendChild($transac);

		/*$monetarygrsub = $this->xml->createElement('sac:AdditionalMonetaryTotal');
		$monetarygrsub->appendChild($this->xml->createElement('cbc:ID',1005));
		$monetarygrsub->appendChild($this->xml->createElement('cbc:Name','Total valor de venta - operaciones gravadas'));
		$pay = $this->xml->createElement('cbc:PayableAmount',number_format($data['total']/1.18,2,'.',''));
		$pa = $this->xml->createAttribute('currencyID');
		$pa->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$pay->appendChild($pa);
		$monetarygrsub->appendChild($pay);
		$inform->appendChild($monetarygrsub);*/
		$exten2 = $this->xml->createElement('ext:UBLExtension');
		$content2 = $this->xml->createElement('ext:ExtensionContent');
		$this->raiz->appendChild($this->xml->createElement('cbc:ID',$data['num_serie'].'-'.$data['num_documento']));
		$this->raiz->appendChild($this->xml->createElement('cbc:IssueDate',substr($data['fecha'], 0, 10)));
		$this->raiz->appendChild($this->xml->createElement('cbc:IssueTime',substr($data['fecha'], 11)));	
		$this->raiz->appendChild($this->xml->createElement('cbc:InvoiceTypeCode','03'));
		if($data['moneda'] == 1)	
			$this->raiz->appendChild($this->xml->createElement('cbc:DocumentCurrencyCode','PEN'));
		else
			$this->raiz->appendChild($this->xml->createElement('cbc:DocumentCurrencyCode','USD'));
		$cacsignature = $this->xml->createElement('cac:Signature');
		$cacsignature->appendChild($this->xml->createElement('cbc:ID',$data['num_serie'].'-'.$data['num_documento']));
		$signatoryparty = $this->xml->createElement('cac:SignatoryParty');
		$cacpartyname = $this->xml->createElement('cac:PartyName');
		$name = $this->xml->createElement('cbc:Name');
		$name->appendChild($this->xml->createCDATASection('CARDEPLAS´T PERU S.A.C.'));
		$cacpartyname->appendChild($name);
		$partyidentificacion = $this->xml->createElement('cac:PartyIdentification');
		$partyidentificacion->appendChild($this->xml->createElement('cbc:ID','20523132441'));
		$signatoryparty->appendChild($partyidentificacion);
		$signatoryparty->appendChild($cacpartyname);
		$cacsignature->appendChild($signatoryparty);
		$digitalsigna = $this->xml->createElement('cac:DigitalSignatureAttachment');
		$externalreference = $this->xml->createElement('cac:ExternalReference');
		$externalreference->appendChild($this->xml->createElement('cbc:URI','20523132441-'.$data['num_serie'].'-'.$data['num_documento']));
		$digitalsigna->appendChild($externalreference);
		$cacsignature->appendChild($digitalsigna);
		$this->raiz->appendChild($cacsignature);
		//para el ruc
		$ruc = $this->xml->createElement('cac:AccountingSupplierParty');
		$ruc->appendChild($this->xml->createElement('cbc:CustomerAssignedAccountID','20523132441'));
		$ruc->appendChild($this->xml->createElement('cbc:AdditionalAccountID',6));
		//dentro del ruc
		$party = $this->xml->createElement('cac:Party');
		$p = $this->xml->createElement('cac:PartyName');
		$na = $this->xml->createElement('cbc:Name');
		$nam = $this->xml->createCDATASection('CARDEPLAS´T PERU S.A.C.');
		$na->appendChild($nam);
		$p->appendChild($na);
		$party->appendChild($p);
		$pl = $this->xml->createElement('cac:PartyLegalEntity');
		$na = $this->xml->createElement('cbc:RegistrationName');
		$nam = $this->xml->createCDATASection('CARDEPLAS´T PERU S.A.C.');
		$na->appendChild($nam);
		$pl->appendChild($na);
		$party->appendChild($pl);
		$ruc->appendChild($party);

		//datos del cliente
		$counting = $this->xml->createElement('cac:AccountingCustomerParty');
		$party = $this->xml->createElement('cac:Party');
		$pl = $this->xml->createElement('cac:PartyLegalEntity');
		$reg = $this->xml->createElement('cbc:RegistrationName');
		$nam = $this->xml->createCDATASection($data['nombres']);
		$reg->appendChild($nam);
		$pl->appendChild($reg);
		$party->appendChild($pl);
		$counting->appendChild($this->xml->createElement('cbc:CustomerAssignedAccountID',$data['dni']));
		$counting->appendChild($this->xml->createElement('cbc:AdditionalAccountID','1'));
		$counting->appendChild($party);

		$to = $this->xml->createElement('cac:LegalMonetaryTotal');
		$t = $this->xml->createElement('cbc:PayableAmount',number_format($data['total'],2,'.',''));
		$pr = $this->xml->createAttribute('currencyID');
		$pr->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$t->appendChild($pr);
		$to->appendChild($t);

		$content->appendChild($inform);
		$exten->appendChild($content);
		$extens->appendChild($exten);
		$exten2->appendChild($content2);
		$extens->appendChild($exten2);

		$this->raiz->appendChild($ruc);
		$this->raiz->appendChild($counting);
		$this->raiz->appendChild($to);

		//para la lista de items
		foreach ($items as $key => $value) {
			//INVOCE LINE
			$lineinvoce = $this->xml->createElement('cac:InvoiceLine');
			$lineinvoce->appendChild($this->xml->createElement('cbc:ID',($key+1)));
			//INVOCEQUANTITY
			$e = $this->xml->createElement('cbc:InvoicedQuantity',number_format($value['cantidad'],2,'.',''));
			$et = $this->xml->createAttribute('unitCode');
			$et->value = 'BX';
			$e->appendChild($et);
			$lineinvoce->appendChild($e);
			//LINEEXTENSIONAMOUNT
			$monto = $this->xml->createElement('cbc:LineExtensionAmount',number_format($value['precio_venta_f']*$value['cantidad'],2,'.',''));
			$p = $this->xml->createAttribute('currencyID');
			$p->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$monto->appendChild($p);
			$lineinvoce->appendChild($monto);

			//PRICINGREFERENCE
			$price = $this->xml->createElement('cac:PricingReference');
			$condi = $this->xml->createElement('cac:AlternativeConditionPrice');
			$precio = $this->xml->createElement('cbc:PriceAmount',number_format($value['precio_venta_f'],2,'.',''));
			$pr = $this->xml->createAttribute('currencyID');
			$pr->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$precio->appendChild($pr);
			$preciotype = $this->xml->createElement('cbc:PriceTypeCode','01');
			$condi->appendChild($precio);
			$condi->appendChild($preciotype);
			$price->appendChild($condi);
			$lineinvoce->appendChild($price);


			//TAXTOTAL
			$tax = $this->xml->createElement('cac:TaxTotal');
			$taxamount = $this->xml->createElement('cbc:TaxAmount',number_format($value['precio_venta_f']-$value['precio_venta_f']/($data['impuesto']/100+1),2,'.',''));
			$pre = $this->xml->createAttribute('currencyID');
			$pre->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$taxamount->appendChild($pre);
			//$taxamount->appendChild($pr);
			$taxamounts = $this->xml->createElement('cbc:TaxAmount',number_format($value['precio_venta_f']-$value['precio_venta_f']/($data['impuesto']/100+1),2,'.',''));
			$pres = $this->xml->createAttribute('currencyID');
			$pres->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$taxamounts->appendChild($pres);
			$tax->appendChild($taxamounts);
			$taxsubtotal = $this->xml->createElement('cac:TaxSubtotal');
			$taxsubtotal->appendChild($taxamount);
			$taxsubtotal->appendChild($this->xml->createElement('cbc:Percent',number_format($data['impuesto'],2,'.','')));
			$textcategori = $this->xml->createElement('cac:TaxCategory');
			$textcategori->appendChild($this->xml->createElement('cbc:TaxExemptionReasonCode',40));
			$taxscheme = $this->xml->createElement('cac:TaxScheme');
			$taxscheme->appendChild($this->xml->createElement('cbc:ID',1000));
			$taxscheme->appendChild($this->xml->createElement('cbc:Name','IGV'));
			$taxscheme->appendChild($this->xml->createElement('cbc:TaxTypeCode','VAT'));
			$textcategori->appendChild($taxscheme);
			$taxsubtotal->appendChild($textcategori);
			$tax->appendChild($taxsubtotal);
			$lineinvoce->appendChild($tax);


			//ITEM
			$i = $this->xml->createElement('cac:Item');
			$na = $this->xml->createElement('cbc:Description');
			$nam = $this->xml->createCDATASection($value['nombre_producto']);
			$na->appendChild($nam);
			$i->appendChild($na);
			$lineinvoce->appendChild($i);

			$pri = $this->xml->createElement('cac:Price');
			$amount = $this->xml->createElement('cbc:PriceAmount',number_format($value['precio_venta_f'],2,'.',''));
			$pr = $this->xml->createAttribute('currencyID');
			$pr->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$amount->appendChild($pr);
			$pri->appendChild($amount);
			$lineinvoce->appendChild($pri);

			$this->raiz->appendChild($lineinvoce);
		}
		$this->agregaRaiz();
	}

	public function cargadataFactura($data = array(),$items = array()){
		if(count($data) == 0)
			return 0;
		$extens = $this->xml->createElement('ext:UBLExtensions');
		$this->raiz->appendChild($extens);
		//otros agregados a la raiz
		$this->raiz->appendChild($this->xml->createElement('cbc:UBLVersionID','2.0'));
		$this->raiz->appendChild($this->xml->createElement('cbc:CustomizationID','1.0'));
		$exten = $this->xml->createElement('ext:UBLExtension');
		$content = $this->xml->createElement('ext:ExtensionContent');
		$inform = $this->xml->createElement('sac:AdditionalInformation');
		$transac = $this->xml->createElement('sac:SUNATTransaction');
		$transac->appendChild($this->xml->createElement('cbc:ID','01'));
		$property = $this->xml->createElement('sac:AdditionalProperty');
		//$this->load->library('utiles');
		$property->appendChild($this->xml->createElement('cbc:ID',1000));
		$property->appendChild($this->xml->createElement('cbc:Name','Monto en Letras'));
		$tota = explode('.',number_format($data['total'],2));
		$na = $this->xml->createElement('cbc:Value');
		$moneda = ['DOLARES AMERICANOS','SOLES','DOLARES AMERICANOS'];
		// $nam = $this->xml->createCDATASection('SON:  '.$this->utiles->leterNumber($data['total']).' Y '.(isset($tota[1]) ? $tota[1] : '00').'/100 '.$moneda[$data['moneda']]);
		$nam = $this->xml->createCDATASection('SON:  '.$this->utiles->leterNumber($data['total']));
		$na->appendChild($nam);
		$property->appendChild($na);
		$monetarygrav = $this->xml->createElement('sac:AdditionalMonetaryTotal');
		$monetarygrav->appendChild($this->xml->createElement('cbc:ID',1001));
		$monetarygrav->appendChild($this->xml->createElement('cbc:Name','Total valor de venta - operaciones gravadas'));
		$pay = $this->xml->createElement('cbc:PayableAmount',number_format($data['total'],2,'.',''));
		$pa = $this->xml->createAttribute('currencyID');
		$pa->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$pay->appendChild($pa);
		$monetarygrav->appendChild($pay);
		$monetaryin = $this->xml->createElement('sac:AdditionalMonetaryTotal');
		$monetaryin->appendChild($this->xml->createElement('cbc:ID',1002));
		$monetaryin->appendChild($this->xml->createElement('cbc:Name','Total valor de venta - operaciones inafectas'));
		$pay = $this->xml->createElement('cbc:PayableAmount',0.0);
		$pa = $this->xml->createAttribute('currencyID');
		$pa->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$pay->appendChild($pa);
		$monetaryin->appendChild($pay);
		$monetaryex = $this->xml->createElement('sac:AdditionalMonetaryTotal');
		$monetaryex->appendChild($this->xml->createElement('cbc:ID',1003));
		$monetaryex->appendChild($this->xml->createElement('cbc:Name','Total valor de venta - operaciones exoneradas'));
		$pay = $this->xml->createElement('cbc:PayableAmount',0.0);
		$pa = $this->xml->createAttribute('currencyID');
		$pa->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$pay->appendChild($pa);
		$monetaryex->appendChild($pay);
		$inform->appendChild($monetarygrav);
		// $inform->appendChild($monetaryin);
		// $inform->appendChild($monetaryex);
		$inform->appendChild($property);
		$inform->appendChild($transac);
		/*$monetarygrsub = $this->xml->createElement('sac:AdditionalMonetaryTotal');
		$monetarygrsub->appendChild($this->xml->createElement('cbc:ID',1005));
		$monetarygrsub->appendChild($this->xml->createElement('cbc:Name','Total valor de venta - operaciones gravadas'));
		$pay = $this->xml->createElement('cbc:PayableAmount',number_format($data['total']/1.18,2,'.',''));
		$pa = $this->xml->createAttribute('currencyID');
		$pa->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$pay->appendChild($pa);
		$monetarygrsub->appendChild($pay);
		$inform->appendChild($monetarygrsub);*/
		$exten2 = $this->xml->createElement('ext:UBLExtension');
		$content2 = $this->xml->createElement('ext:ExtensionContent');
		$this->raiz->appendChild($this->xml->createElement('cbc:ID',$data['num_serie'].'-'.$data['num_documento']));
		$this->raiz->appendChild($this->xml->createElement('cbc:IssueDate',substr($data['fecha'], 0, 10)));
		$this->raiz->appendChild($this->xml->createElement('cbc:IssueTime',substr($data['fecha'], 11)));	
		$this->raiz->appendChild($this->xml->createElement('cbc:InvoiceTypeCode','01'));
		if($data['moneda'] == 1)	
			$this->raiz->appendChild($this->xml->createElement('cbc:DocumentCurrencyCode','PEN'));
		else
			$this->raiz->appendChild($this->xml->createElement('cbc:DocumentCurrencyCode','USD'));
		$cacsignature = $this->xml->createElement('cac:Signature');
		$cacsignature->appendChild($this->xml->createElement('cbc:ID',$data['num_serie'].'-'.$data['num_documento']));
		$signatoryparty = $this->xml->createElement('cac:SignatoryParty');
		$cacpartyname = $this->xml->createElement('cac:PartyName');
		$name = $this->xml->createElement('cbc:Name');
		$name->appendChild($this->xml->createCDATASection('CARDEPLAS´T PERU S.A.C.'));
		$cacpartyname->appendChild($name);
		$partyidentificacion = $this->xml->createElement('cac:PartyIdentification');
		$partyidentificacion->appendChild($this->xml->createElement('cbc:ID','20523132441'));
		$signatoryparty->appendChild($partyidentificacion);
		$signatoryparty->appendChild($cacpartyname);
		$cacsignature->appendChild($signatoryparty);
		$digitalsigna = $this->xml->createElement('cac:DigitalSignatureAttachment');
		$externalreference = $this->xml->createElement('cac:ExternalReference');
		$externalreference->appendChild($this->xml->createElement('cbc:URI','20523132441-'.$data['num_serie'].'-'.$data['num_documento']));
		$digitalsigna->appendChild($externalreference);
		$cacsignature->appendChild($digitalsigna);
		$this->raiz->appendChild($cacsignature);
		//para el ruc
		$ruc = $this->xml->createElement('cac:AccountingSupplierParty');
		$ruc->appendChild($this->xml->createElement('cbc:CustomerAssignedAccountID','20523132441'));
		$ruc->appendChild($this->xml->createElement('cbc:AdditionalAccountID',6));
		//dentro del ruc
		$party = $this->xml->createElement('cac:Party');
		$p = $this->xml->createElement('cac:PartyName');
		$na = $this->xml->createElement('cbc:Name');
		$nam = $this->xml->createCDATASection('CARDEPLAS´T PERU S.A.C.');
		$na->appendChild($nam);
		$p->appendChild($na);
		$party->appendChild($p);
		$pl = $this->xml->createElement('cac:PartyLegalEntity');
		$na = $this->xml->createElement('cbc:RegistrationName');
		$nam = $this->xml->createCDATASection('CARDEPLAS´T PERU S.A.C.');
		$na->appendChild($nam);
		$pl->appendChild($na);
		$party->appendChild($pl);
		$ruc->appendChild($party);
		//datos del cliente
		$counting = $this->xml->createElement('cac:AccountingCustomerParty');
		$party = $this->xml->createElement('cac:Party');
		$pl = $this->xml->createElement('cac:PartyLegalEntity');
		$reg = $this->xml->createElement('cbc:RegistrationName');
		$nam = $this->xml->createCDATASection($data['razon_social']);
		$reg->appendChild($nam);
		$pl->appendChild($reg);
		$party->appendChild($pl);
		$counting->appendChild($this->xml->createElement('cbc:CustomerAssignedAccountID',$data['ruc']));
		$counting->appendChild($this->xml->createElement('cbc:AdditionalAccountID','6'));
		$counting->appendChild($party);
		$to = $this->xml->createElement('cac:LegalMonetaryTotal');
		$t = $this->xml->createElement('cbc:PayableAmount',number_format($data['total'],2,'.',''));
		$pr = $this->xml->createAttribute('currencyID');
		$pr->value = $data['moneda'] == 1 ? 'PEN' : 'USD';
		$t->appendChild($pr);
		$to->appendChild($t);
		$content->appendChild($inform);
		$exten->appendChild($content);
		$extens->appendChild($exten);
		$exten2->appendChild($content2);
		$extens->appendChild($exten2);
		$this->raiz->appendChild($ruc);
		$this->raiz->appendChild($counting);
		$this->raiz->appendChild($to);
		//para la lista de items
		foreach ($items as $key => $value) {
			//INVOCE LINE
			$lineinvoce = $this->xml->createElement('cac:InvoiceLine');
			$lineinvoce->appendChild($this->xml->createElement('cbc:ID',($key+1)));
			//INVOCEQUANTITY
			$e = $this->xml->createElement('cbc:InvoicedQuantity',number_format($value['cantidad'],2,'.',''));
			$et = $this->xml->createAttribute('unitCode');
			$et->value = 'BX';
			$e->appendChild($et);
			$lineinvoce->appendChild($e);
			//LINEEXTENSIONAMOUNT
			$monto = $this->xml->createElement('cbc:LineExtensionAmount',number_format($value['precio_venta_f']*$value['cantidad'],2,'.',''));
			$p = $this->xml->createAttribute('currencyID');
			$p->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$monto->appendChild($p);
			$lineinvoce->appendChild($monto);
			//PRICINGREFERENCE
			$price = $this->xml->createElement('cac:PricingReference');
			$condi = $this->xml->createElement('cac:AlternativeConditionPrice');
			$precio = $this->xml->createElement('cbc:PriceAmount',number_format($value['precio_venta_f'],2,'.',''));
			$pr = $this->xml->createAttribute('currencyID');
			$pr->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$precio->appendChild($pr);
			$preciotype = $this->xml->createElement('cbc:PriceTypeCode','01');
			$condi->appendChild($precio);
			$condi->appendChild($preciotype);
			$price->appendChild($condi);
			$lineinvoce->appendChild($price);
			//TAXTOTAL
			$tax = $this->xml->createElement('cac:TaxTotal');
			$taxamount = $this->xml->createElement('cbc:TaxAmount',number_format($value['precio_venta_f']-$value['precio_venta_f']/($data['impuesto']/100+1),2,'.',''));
			$pre = $this->xml->createAttribute('currencyID');
			$pre->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$taxamount->appendChild($pre);
			//$taxamount->appendChild($pr);
			$taxamounts = $this->xml->createElement('cbc:TaxAmount',number_format($value['precio_venta_f']-$value['precio_venta_f']/($data['impuesto']/100+1),2,'.',''));
			$pres = $this->xml->createAttribute('currencyID');
			$pres->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$taxamounts->appendChild($pres);
			$tax->appendChild($taxamounts);
			$taxsubtotal = $this->xml->createElement('cac:TaxSubtotal');
			$taxsubtotal->appendChild($taxamount);
			$taxsubtotal->appendChild($this->xml->createElement('cbc:Percent',number_format($data['impuesto'],2,'.','')));
			$textcategori = $this->xml->createElement('cac:TaxCategory');
			$textcategori->appendChild($this->xml->createElement('cbc:TaxExemptionReasonCode',40));
			$taxscheme = $this->xml->createElement('cac:TaxScheme');
			$taxscheme->appendChild($this->xml->createElement('cbc:ID',1000));
			$taxscheme->appendChild($this->xml->createElement('cbc:Name','IGV'));
			$taxscheme->appendChild($this->xml->createElement('cbc:TaxTypeCode','VAT'));
			$textcategori->appendChild($taxscheme);
			$taxsubtotal->appendChild($textcategori);
			$tax->appendChild($taxsubtotal);
			$lineinvoce->appendChild($tax);
			//ITEM
			$i = $this->xml->createElement('cac:Item');
			$na = $this->xml->createElement('cbc:Description');
			$nam = $this->xml->createCDATASection($value['nombre_producto']);
			$na->appendChild($nam);
			$i->appendChild($na);
			$lineinvoce->appendChild($i);
			$pri = $this->xml->createElement('cac:Price');
			$amount = $this->xml->createElement('cbc:PriceAmount',number_format($value['precio_venta_f'],2,'.',''));
			$pr = $this->xml->createAttribute('currencyID');
			$pr->value = $data['moneda'] == '1' ? 'PEN' : 'USD';
			$amount->appendChild($pr);
			$pri->appendChild($amount);
			$lineinvoce->appendChild($pri);
			$this->raiz->appendChild($lineinvoce);
		}
		$this->agregaRaiz();
	}

}