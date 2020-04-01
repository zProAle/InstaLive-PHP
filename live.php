<?php
// controlo che sia da cli e non web
if (php_sapi_name() !== "cli") {
    die("You may only run this script inside of the PHP Command Line! If you did run this in the command line, please report: \"" . php_sapi_name());
} 

logM("Loading InstaLive-PHP by zProAle");

require("config.php"); 

$verification_method = 1; 	// 0 = SMS 1 = Email per la challange

class ExtendedInstagram extends \InstagramAPI\Instagram {
	public function changeUser($username2, $password2) {$this->_setUser( $username2, $password2 );}
}

function readln( $prompt ) { // funzione per inserire il codice di verifica
	if ( PHP_OS === 'WINNT' ) {echo "$prompt ";return trim( (string) stream_get_line( STDIN, 6, "\n" ) );}
	return trim( (string) readline( "$prompt " ) );
}

$ig = new ExtendedInstagram(false, $truncatedDebug);

if ($username == "USERNAME" || $password == "PASSWORD") { // controllo che non siano le predefinite
    logM("Non hai cambiato username e password");
    exit();
}

use InstagramAPI\Request\Live;
use InstagramAPI\Response\Model\User;
use InstagramAPI\Response\Model\Comment;

try {
	// tento acesso all'account
	$ig->login($username, $password);
	
} catch (\Exception $exception) {
	$response = $exception->getResponse();
	$fatalError = "Fatal error ".PHP_EOL;
	file_put_contents("fatals.txt",$fatalError,FILE_APPEND);
	file_put_contents("fatals.txt",$exception,FILE_APPEND);
	file_put_contents("fatals.txt",$response,FILE_APPEND);
	file_put_contents("fatals.txt",var_dump($exception),FILE_APPEND);
	file_put_contents("fatals.txt",print_r($exception),FILE_APPEND);
	
	if ($response->getErrorType() === 'checkpoint_challenge_required') { // effettuo la richiesta di challange
		sleep(3);
		$checkApiPath = substr( $response->getChallenge()->getApiPath(), 1);
		$customResponse = $ig->request($checkApiPath)->setNeedsAuth(false)->addPost('choice', $verification_method)->addPost('_uuid', $ig->uuid)
		->addPost('guid', $ig->uuid)->addPost('device_id', $ig->device_id)->addPost('_uid', $ig->account_id)->addPost('_csrftoken', $ig->client->getToken())->getDecodedResponse();
	} else { // non posso risolvere il check point della challange
		echo 'Non riesco a risolvere la pre-challange'.PHP_EOL;
		exit();
	}
	
	try { // faccio inserire il codice ottenuto per verificare la challange
		if ($customResponse['status'] === 'ok' && $customResponse['action'] === 'close') {
			exit();
		}
		$code = readln( 'Inserisci il codice ricevuto via ' . ( $verification_method ? 'email' : 'sms' ) . ':' );
		$ig->changeUser($username, $password);
		$customResponse = $ig->request($checkApiPath)->setNeedsAuth(false)->addPost('security_code', $code)->addPost('_uuid', $ig->uuid)->addPost('guid', $ig->uuid)->addPost('device_id', $ig->device_id)->addPost('_uid', $ig->account_id)->addPost('_csrftoken', $ig->client->getToken())->getDecodedResponse();
		if ($customResponse['status'] === 'ok' && (int) $customResponse['logged_in_user']['pk'] === (int) $user_id ) {
		} else {
			file_put_contents("customResponse.txt",var_dump($customResponse),FILE_APPEND);
			file_put_contents("customResponse.txt",print_r($customResponse),FILE_APPEND);
		}
		echo 'Puo essere necessarrio riavviare il programma per fixare!';
	}
	catch ( Exception $ex ) {
		echo $ex->getMessage();
	}
}

try{
	if (!$ig->isMaybeLoggedIn) {
        logM("Non sei loggato!");
        exit();
    }
	
	logM("Creazione streaming in corso...");
	
	// creo la stream
	$stream = $ig->live->create();
	$broadcastId = $stream->getBroadcastId();
	$ig->live->start($broadcastId);
	$streamUploadUrl = $stream->getUploadUrl();
	
	// grabbo id e url
	$split = preg_split("[" . $broadcastId . "]", $streamUploadUrl);
	$streamUrl = $split[0];
	$streamKey = $broadcastId . $split[1];
	
	// visualizzo url e key
	logM("================================ Stream URL ================================\n" . $streamUrl . "\n================================ Stream URL ================================");
	logM("======================== Current Stream Key ========================\n" . $streamKey . "\n======================== Current Stream Key ========================");
	logM("\n^^ Perfavore avvia la diretta in OBS/Streaming tramite questo url e key ^^ \n");
	logM("La live streams è inzia, scrivi un comando:");
    newCommand($ig->live, $broadcastId, $streamUrl, $streamKey);
	logM("Ci sono degli errori stoppo la diretta!");
    $ig->live->getFinalViewerList($broadcastId);
    $ig->live->end($broadcastId);
}catch (\Exception $e) {
	echo 'Errore: impossibile creare la live: ' . $e->getMessage() . "\n";
}

/**
 * The handler for interpreting the commands passed via the command line. thanks to https://github.com/machacker16/
 */
 
function newCommand(Live $live, $broadcastId, $streamUrl, $streamKey) {
    print "\n> ";
    $handle = fopen ("php://stdin","r");
    $line = trim(fgets($handle));
    if($line == 'ecomments') {
        $live->enableComments($broadcastId);
        logM("Commenti abilitati!");
    } elseif ($line == 'dcomments') {
        $live->disableComments($broadcastId);
        logM("Commenti dibilitati!");
    } elseif ($line == 'stop' || $line == 'end') {
        fclose($handle);
        $live->getFinalViewerList($broadcastId);
        $live->end($broadcastId);
        logM("La live è terminata! Vuoi salavare la live per 24h sul tuo profilo? Scegli \"si\" o \"no\" ");
        print "> ";
        $handle = fopen ("php://stdin","r");
        $archived = trim(fgets($handle));
        if ($archived == 'yes') {
            logM("Aggiungo la live..");
            $live->addToPostLive($broadcastId);
            logM("Live aggiunta al profilo!");
        }
        logM("Chiusura in corso..");
        exit();
    } elseif ($line == 'url') {
        logM("================================ Stream URL ================================\n".$streamUrl."\n================================ Stream URL ================================");
    } elseif ($line == 'key') {
        logM("======================== Current Stream Key ========================\n".$streamKey."\n======================== Current Stream Key ========================");
    } elseif ($line == 'info') {
        $info = $live->getInfo($broadcastId);
        $status = $info->getStatus();
        $muted = var_export($info->is_Messages(), true);
        $count = $info->getViewerCount();
        logM("Info:\nStato: $status\nMutata: $muted\nNumero visualizzatori: $count");
    } elseif($line == 'commenta'){
		logM("Inserisci il testo da inviare: ");
        print "> ";
        $handle = fopen ("php://stdin","r");
        $text = trim(fgets($handle));
        if ($text !== "") {
            $live->comment($broadcastId, $text);
            logM("Commento inviato!");
        } else {
            logM("Il testo del commento è vuoto!");
        }
	} elseif ($line == 'viewers') {
        logM("Viewers:");
        $live->getInfo($broadcastId);
        foreach ($live->getViewerList($broadcastId)->getUsers() as &$cuser) {
            logM("@".$cuser->getUsername()." (".$cuser->getFullName().")");
        }
    } elseif ($line == 'help') {
        logM("Commandi:\nhelp - Stampa questo messaggio\nurl - Stampa url della diretta\nkey - Stampa la streams key\ninfo - Ottengo le info\nviewers - Stampo i viewers\necomments - Attivo i commenti\ndcomments - Disattivo i commenti\ncommenta - Manda un messaggio alla diretta\nstop - Interrompo la live");
    } else {
       logM("Comando non trovato! Scrivi \"help\" per la lista comandi");
    }
    fclose($handle);
    newCommand($live, $broadcastId, $streamUrl, $streamKey);
}

/**
 * Logs a message in console but it actually uses new lines.
 * @param string $message message to be logged.
 */
function logM($message)
{
    print $message . "\n";
}
