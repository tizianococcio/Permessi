<?php
class Permessi 
{
	/**
	 * PDO connection instance
	 * @var PDO
	 */
	protected $_db;
    
    /**
     * List of permissions array
     * @var array
     */
    private $_listaPermessi;
	
	/**
	 * Constructor
     * @param   PDO     $db
	 */
	public function __construct($db) {
		$this->_db = $db;
	}
    
    
    /**
     * Generates a new kind of action to be given to users
     * @param   string  $azione
     * @param   string  $descrizione
     * @return  mixed
     */
    public function nuovo($azione, $descrizione) {
        if (!$azione && !$descrizione) {
            return false;
        }
        
        $st = $this->_db->prepare("INSERT INTO permessi (azione, descrizione) VALUE (:azione, :descrizione)");
        $st->bindValue(":azione", $azione, PDO::PARAM_STR);
        $st->bindValue(":descrizione", $descrizione, PDO::PARAM_STR);
        if ($st->execute()) {
            return ($this->_db->lastInsertID());
        } else {
            return false;
        }
    }

    /**
     * Provides the list of actions available, 
     * if a user's permission are given as parameter each action 
     * will be checked against it resulting in a boolean value (active or not)
     * @param   int     $permessi_utente
     * @return  array
     */
	public function getListaPermessi($permessi_utente = null) {
	    if (!is_array($this->_listaPermessi)) {
    		$st = $this->_db->prepare("SELECT * FROM permessi ORDER BY descrizione");
    		$st->execute();
    		
    		$lp = array();
    		while ($d = $st->fetchObject()) {
    			$lp[] = array(
    				"id" => $d->id,
    				"azione" => $d->azione,
    				"descrizione" => $d->descrizione,
    				"attivo" => (($permessi_utente !== null)? ((pow(2, $d->id - 1) & $permessi_utente) ? 1 : 0) : null)
    			);
    		}
    		$this->_listaPermessi = $lp;
	    }
        return $this->_listaPermessi;
	}
    
    /**
     * Check whether a user is enabled to perform a specific kind of action ($azione)
     * @param   int     $azione
     * @param   int     $livello_utente
     * @return  boolean
     */
    private function controllaAzioneUtente($azione, $livello_utente) {
        $auth = 0;
    	$st = $this->_db->prepare("SELECT id FROM permessi WHERE azione = :azione");
        $st->bindValue(":azione", $azione, PDO::PARAM_STR);
    	$st->execute();
        if ($st->rowCount() > 0) {
            $id = $st->fetchObject()->id;
            $auth = ((pow(2, $id - 1) & $livello_utente) ? 1 : 0);
        }
        return $auth;
    }
    
    /**
     * Check if every action exists in the 'permissions' table
     * @param   array   $azioni
     * @return  always  true
     */
    protected function controllaPresenza($azioni) {
        foreach ($azioni as $k => $azione) {
            $st = $this->_db->prepare("SELECT id FROM permessi WHERE azione = :azione");
            $st->bindValue(":azione", $azione, PDO::PARAM_STR);
            $st->execute();
            if ($st->rowCount() == 0) {
                $st_insert = $this->_db->prepare("INSERT INTO permessi (azione, descrizione) VALUES (:azione, :descrizione)");
                $st_insert->bindValue(":azione", $azione, PDO::PARAM_STR);
                $st_insert->bindValue(":descrizione", strtolower(str_replace("_", " ", $azione)), PDO::PARAM_STR);
                $st_insert->execute();
            }
        }
        return true;
    }
    
    /**
     * Is the method name associated with an authorized action?
     * @param   string  $nome_metodo
     * @param   int     $livello_utente
     * @param   array   $azioni
     * @return  boolean
     */
    protected function controllaMetodo($nome_metodo, $livello_utente, $azioni) {
        $auth = 0;
        if (array_key_exists($nome_metodo, $azioni)) {
            $azione = $azioni[$nome_metodo];
            $auth = $this->controllaAzioneUtente($azione, $livello_utente);
        } else {
            $auth = true;
        }
        return $auth;
    }
}

