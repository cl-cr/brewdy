<?php

class DBFactory {
  public static function getMysqlConnexionWithPDO() {
    $db = new PDO('mysql:host=localhost;dbname=Brewdy', '', '', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));

    return $db;
  }
  public static function getMysqlConnexionWithPDOLocal() {
    $db = new PDO('mysql:host=localhost;dbname=brewdy', '', '', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $db;
  }
}

class users {
	protected $erreurs = [],
			  $IdUser,
			  $Pseudo,
			  $Password,
			  $Mail;


	/* Constantes liées à la gestion des erreurs */
	const USER_INVALIDE = 1;
	const PASSWORD_INVALIDE = 2;
	const MAIL_INVALIDE = 3;

	/* Constructeur et fonction d'hydratation */
	public function __construct($valeurs = []) {
		if(!empty($valeurs)) {
			$this->hydrate($valeurs);
		}
	}

	public function hydrate($donnees) {
		foreach ($donnees as $attribut => $valeur) {
			$methode = 'set'.ucfirst($attribut);

			if(is_callable([$this, $methode])) {
				$this->$methode($valeur);
			}
		}
	}

	/* SETTERS */
	public function setIdUser($IdUser) {
		$this->IdUser = (int) $IdUser;
	}

	public function setPseudo($Pseudo) {
		if(empty($Pseudo)) {
			$this->erreurs[] = self::USER_INVALIDE;
		} else {
			$this->Pseudo = $Pseudo;
		}
	}

	public function setPassword($Password) {
		if(empty($Password)) {
			$this->erreurs[] = self::PASSWORD_INVALIDE;
		} else {
			$this->Password = $Password;
		}
	}

	public function setMail($Mail) {
		if (!preg_match("#^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]{2,}\.[a-z]{2,4}$#", $Mail)) {
			$this->erreurs[] = self::MAIL_INVALIDE;
		} else {
			$this->Mail = $Mail;
		}
	}

	/* GETTERS */
	public function getErreurs() {
		return $this->erreurs;
	}

	public function getIdUser() {
		return $this->IdUser;
	}

	public function getPseudo() {
		return $this->Pseudo;
	}

	public function getPassword() {
		return $this->Password;
	}

	public function getMail() {
		return $this->Mail;
	}

	public function getInformationsUser() {
		echo 'Id : '.$this->IdUser.'<br />Pseudo : '.$this->Pseudo.'<br />Mail : '.$this->Mail;
	}
}

class usersManagerPDO {
	protected $db;

	const PSEUDO_EXIST = 1;
	const MAIL_EXIST = 2;
	const CONNEXION_ERROR = 3;

	public function __construct(PDO $db) {
		$this->db = $db;
	}

	public function add(users $user) {
		$requete = $this->db->prepare('INSERT INTO Users(Pseudo, Password, Mail, DateInscription) VALUES(:Pseudo, :Password, :Mail, NOW())');

		$requete->bindValue(':Pseudo', $user->getPseudo());
		$requete->bindValue(':Password', $user->getPassword());
		$requete->bindValue(':Mail', $user->getMail());

		$requete->execute();
	}

	public function update(users $user) {
		$requete = $this->db->prepare('UPDATE Users SET Pseudo = :Pseudo, Password = :Password, Mail = :Mail WHERE IdUser = :IdUser');

		$requete->bindValue(':Pseudo', $user->getPseudo());
		$requete->bindValue(':Password', $user->getPassword());
		$requete->bindValue(':Mail', $user->getMail());
		$requete->bindValue(':IdUser', $user->getIdUser(), PDO::PARAM_INT);

		$requete->execute();
	}

	public function delete(users $user) {
		$this->db->exec('DELETE FROM Users WHERE IdUser = '. $user->getIdUser());
	}

	/* Teste si le couple Pseudo / Password ou Mail / Password est correct afin de valider une connexion */
	public function isConnexionOk($Identifiant, $Password) {
		if (preg_match("#^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$#", $Identifiant)) {
			$requete = $this->db->prepare('SELECT * FROM Users WHERE (Mail = :Mail AND Password = :Password)');
			$requete->bindValue(':Mail', $Identifiant);
			$requete->bindValue(':Password', $Password);
			$requete->execute();
			$user = $requete->fetch();

			if(empty($user)) {
				$this->erreurs[] = self::CONNEXION_ERROR;
			} else {
				$userConnected = new users(
					[
						'IdUser' => $user['IdUser'],
						'Pseudo' => $user['Pseudo'],
						'Mail' => $user['Mail']
					]
				);
				return $userConnected;
			}
		} else {
			$requete = $this->db->prepare('SELECT * FROM Users WHERE (Pseudo = :Pseudo AND Password = :Password)');
			$requete->bindValue(':Pseudo', $Identifiant);
			$requete->bindValue(':Password', $Password);
			$requete->execute();
			$user = $requete->fetch();

			if(empty($user)) {
				$this->erreurs[] = self::CONNEXION_ERROR;
			} else {
				$userConnected = new users(
					[
						'IdUser' => $user['IdUser'],
						'Pseudo' => $user['Pseudo'],
						'Mail' => $user['Mail']
					]
				);
				return $userConnected;
			}
		}
	}

	public function isInscriptionOk(users $user) {

		$requete = $this->db->prepare('SELECT * FROM Users WHERE (Mail = :Mail OR Pseudo = :Pseudo)');
		$requete->bindValue(':Mail', $user->getMail());
		$requete->bindValue(':Pseudo', $user->getPseudo());
		$requete->execute();
		$user = $requete->fetch();

		if(!empty($user)) {
			return false;
		} else {
			return true;
		}

	}


	public function getErreurs() {
		return $this->erreurs;
	}
}

class cuves {

	protected $erreurs = [],
			  $IdCuve,
			  $NumSerieCuve,
			  $IdUser,
			  $NomCuve;


	/* Constantes liées à la gestion des erreurs */
	const USER_INVALIDE = 1;
	const CUVE_INVALIDE = 2;
	const NUM_SERIE_INVALIDE = 3;

	/* Constructeur et fonction d'hydratation */
	public function __construct($valeurs = []) {
		if(!empty($valeurs)) {
			$this->hydrate($valeurs);
		}
	}

	public function hydrate($donnees) {
		foreach ($donnees as $attribut => $valeur) {
			$methode = 'set'.ucfirst($attribut);

			if(is_callable([$this, $methode])) {
				$this->$methode($valeur);
			}
		}
	}

	/* SETTERS */
	public function setIdCuve($IdCuve) {
		$this->IdCuve = (int) $IdCuve;
	}

	public function setIdUser($IdUser) {
		$this->IdUser = (int) $IdUser;
	}


	public function setNumSerieCuve($NumSerieCuve) {
		if(empty($NumSerieCuve)) {
			$this->erreurs[] = self::NUM_SERIE_INVALIDE;
		} else {
			$this->NumSerieCuve = $NumSerieCuve;
		}
	}

	public function setNomCuve($NomCuve) {
		$this->NomCuve = $NomCuve;
	}

	/* GETTERS */
	public function getErreurs() {
		return $this->erreurs;
	}

	public function getIdCuve() {
		return $this->IdCuve;
	}

	public function getIdUser() {
		return $this->IdUser;
	}

	public function getNumSerieCuve() {
		return $this->NumSerieCuve;
	}

	public function getNomCuve() {
		if(empty($this->NomCuve)) { return 'Ma cuve'; }
		else { return $this->NomCuve; }
	}
}

class cuvesManagerPDO {
	protected $db;

	public function __construct(PDO $db) {
		$this->db = $db;
	}

	public function add(cuves $cuve) {
		$requete = $this->db->prepare('INSERT INTO Cuves(NumSerieCuve, IdUser, NomCuve) VALUES(:NumSerieCuve, :IdUser, :NomCuve)');

		$requete->bindValue(':NumSerieCuve', $cuve->getNumSerieCuve());
		$requete->bindValue(':IdUser', $cuve->getIdUser());
		$requete->bindValue(':NomCuve', $cuve->getNomCuve());

		$requete->execute();
	}

	public function update(cuves $cuve) {
		$requete = $this->db->prepare('UPDATE Cuves SET IdUser = :IdUser, NomCuve = :NomCuve WHERE IdCuve = :IdCuve');

		$requete->bindValue(':IdUser', $cuve->getIdUser());
		$requete->bindValue(':NomCuve', $cuve->getNomCuve());
		$requete->bindValue(':IdCuve', $cuve->getIdCuve(), PDO::PARAM_INT);

		$requete->execute();
	}

	public function delete(cuves $cuve) {
		$this->db->exec('DELETE FROM Cuves WHERE IdCuve = '. $cuve->getIdCuve());
	}

	public function listOfCuves(users $user) {
		$requete = $this->db->prepare('SELECT * FROM Cuves WHERE IdUser = :IdUser');
		$requete->bindValue(':IdUser', $user->getIdUser());
		$requete->execute();
		$listOfCuves = array();

		while($donnees = $requete->fetch()) {
			$listOfCuves[] = new cuves(
				[
					'IdCuve' => $donnees['IdCuve'],
					'NumSerieCuve' => $donnees['NumSerieCuve'],
					'IdUser' => $donnees['IdUser'],
					'NomCuve' => $donnees['NomCuve']
				]);
		}

		return $listOfCuves;
		$requete->closeCursor();
	}

	public function getCuveFromIdCuve($IdCuve) {
		$requete = $this->db->prepare('SELECT * FROM Cuves WHERE IdCuve = :IdCuve');
		$requete->bindValue(':IdCuve', $IdCuve);
		$requete->execute();
		$Cuve = $requete->fetch();

		if(!empty($Cuve)) {

			$CuveObject = new cuves(
				[
					'IdCuve' => $Cuve['IdCuve'],
					'NumSerieCuve' => $Cuve['NumSerieCuve'],
					'IdUser' => $Cuve['IdUser'],
					'NomCuve' => $Cuve['NomCuve']
				]);
		}

		return $CuveObject;
		$requete->closeCursor();

	}

	public function isAttempt(cuves $cuve) {
		$requete = $this->db->prepare('SELECT * FROM Attempts WHERE (IdCuve = :IdCuve AND StartTime < NOW() AND StopTime > NOW())');
		$requete->bindValue(':IdCuve', $cuve->getIdCuve());
		$requete->execute();
		$CuveExist = $requete->fetch();

		if(empty($CuveExist)) {
			$requete->closeCursor();		
			return false;
		} else {			
			$essai = new attempts(
				[
					'IdAttempt' => $CuveExist['IdAttempt'],
					'StartTime' => $CuveExist['StartTime'],
					'StopTime' => $CuveExist['StopTime'],
					'IdUserRecipe' => $CuveExist['IdUserRecipe'],
					'Alcool' => $CuveExist['Alcool'],
					'FinalVolume' => $CuveExist['FinalVolume'],
					'Comment' => $CuveExist['Comment'],
					'IdCuve' => $CuveExist['IdCuve'],					
					'Note' => $CuveExist['Note']
				]);
			$requete->closeCursor();
			return $essai;
		}		
	}
}

class sensors {

	protected $erreurs = [],
			  $IdSensor,
			  $TypeSensor,
			  $NumSerieSensor,
			  $IdCuve;


	/* Constantes liées à la gestion des erreurs */
	const SENSOR_INVALIDE = 1;
	const CUVE_INVALIDE = 2;
	const NUM_SERIE_INVALIDE = 3;
	const TYPE_SENSOR_INVALIDE = 4;

	/* Constructeur et fonction d'hydratation */
	public function __construct($valeurs = []) {
		if(!empty($valeurs)) {
			$this->hydrate($valeurs);
		}
	}

	public function hydrate($donnees) {
		foreach ($donnees as $attribut => $valeur) {
			$methode = 'set'.ucfirst($attribut);

			if(is_callable([$this, $methode])) {
				$this->$methode($valeur);
			}
		}
	}

	/* SETTERS */
	public function setIdSensor($IdSensor) {
		$this->IdSensor = (int) $IdSensor;
	}

	public function setTypeSensor($TypeSensor) {
		$TypesSensorArray = array('Temperature', 'Gaz', 'Bulles');
		
		if(in_array($TypeSensor, $TypesSensorArray)) {
			$this->TypeSensor = $TypeSensor;
		} else {
			$this->erreurs[] = self::TYPE_SENSOR_INVALIDE;
		}
	}

	public function setNumSerieSensor($NumSerieSensor) {
		if(empty($NumSerieSensor)) {
			$this->erreurs[] = self::NUM_SERIE_INVALIDE;
		} else {
			$this->NumSerieSensor = $NumSerieSensor;
		}
	}

	public function setIdCuve($IdCuve) {
		$this->IdCuve = (int) $IdCuve;
	}

	/* GETTERS */
	public function getErreurs() {
		return $this->erreurs;
	}

	public function getIdSensor() {
		return $this->IdSensor;
	}

	public function getTypeSensor() {
		return $this->TypeSensor;
	}

	public function getNumSerieSensor() {
		return $this->NumSerieSensor;
	}

	public function getIdCuve() {
		return $this->IdCuve;
	}
}

class sensorsManagerPDO {
	protected $db;

	public function __construct(PDO $db) {
		$this->db = $db;
	}

	public function add(sensors $sensor) {
		$requete = $this->db->prepare('INSERT INTO Sensors(TypeSensor, NumSerieSensor, IdCuve) VALUES(:TypeSensor, :NumSerieSensor, :IdCuve)');

		$requete->bindValue(':TypeSensor', $sensor->getTypeSensor());
		$requete->bindValue(':NumSerieSensor', $sensor->getNumSerieSensor());
		$requete->bindValue(':IdCuve', $sensor->getIdCuve());

		$requete->execute();
	}

	public function update(sensors $sensor) {
		$requete = $this->db->prepare('UPDATE Sensors SET TypeSensor = :TypeSensor, IdCuve = :IdCuve WHERE IdSensor = :IdSensor');

		$requete->bindValue(':TypeSensor', $sensor->getTypeSensor());
		$requete->bindValue(':IdCuve', $sensor->getIdCuve());
		$requete->bindValue(':IdSensor', $sensor->getIdSensor(), PDO::PARAM_INT);

		$requete->execute();
	}

	public function delete(sensors $sensor) {
		$this->db->exec('DELETE FROM Sensors WHERE IdSensor = '. $sensor->getIdSensor());
	}

	public function sensorExist(sensors $sensor) {
		$requete = $this->db->prepare('SELECT * FROM Sensors WHERE IdSensor = :IdSensor');
		$requete->bindValue(':IdSensor', $sensor->getIdSensor());
		$requete->execute();
		$sensorExist = $requete->fetch();

		if(empty($sensorExist)) {
			return false;
		} else {
			return true;
		}
	}

	public function listOfSensors(cuves $cuve) {
		$requete = $this->db->prepare('SELECT * FROM Sensors WHERE IdCuve = :IdCuve');
		$requete->bindValue(':IdCuve', $cuve->getIdCuve());
		$requete->execute();
		$listOfSensors = array();

		while($donnees = $requete->fetch()) {
			$listOfSensors[] = new sensors(
				[
					'IdSensor' => $donnees['IdSensor'],
					'TypeSensor' => $donnees['TypeSensor'],
					'NumSerieSensor' => $donnees['NumSerieSensor'],
					'IdCuve' => $donnees['IdCuve']
				]);
		}

		return $listOfSensors;
		$requete->closeCursor();
	}

}

class measurements {

	protected $erreurs = [],
			  $IdMesure,
			  $IdSensor,
			  $TimeMesure,
			  $ValueMesure;


	/* Constantes liées à la gestion des erreurs */
	const SENSOR_INVALIDE = 1;
	const TIME_INVALIDE = 2;
	const VALUE_INVALIDE = 3;

	/* Constructeur et fonction d'hydratation */
	public function __construct($valeurs = []) {
		if(!empty($valeurs)) {
			$this->hydrate($valeurs);
		}
	}

	public function hydrate($donnees) {
		foreach ($donnees as $attribut => $valeur) {
			$methode = 'set'.ucfirst($attribut);

			if(is_callable([$this, $methode])) {
				$this->$methode($valeur);
			}
		}
	}

	/* SETTERS */
	public function setIdMesure($IdMesure) {
		$this->IdMesure = (int) $IdMesure;
	}

	public function setIdSensor($IdSensor) {
		$this->IdSensor = (int) $IdSensor;		
	}

	public function setTimeMesure($TimeMesure) {
		$this->TimeMesure = $TimeMesure;		
	}

	public function setValueMesure($ValueMesure) {
		$this->ValueMesure = (float) $ValueMesure;
	}

	/* GETTERS */
	public function getErreurs() {
		return $this->erreurs;
	}

	public function getIdMesure() {
		return $this->IdMesure;
	}

	public function getIdSensor() {
		return $this->IdSensor;
	}

	public function getTimeMesure() {
		return $this->TimeMesure;
	}

	public function getValueMesure() {
		return $this->ValueMesure;
	}
}

class measurementsManagerPDO {
	protected $db;

	public function __construct(PDO $db) {
		$this->db = $db;
	}

	public function add(measurements $measurement) {
		$requete = $this->db->prepare('INSERT INTO Measurements(TimeMesure, ValueMesure, IdSensor) VALUES(NOW(), :ValueMesure, :IdSensor)');

		$requete->bindValue(':IdSensor', $measurement->getIdSensor());
		$requete->bindValue(':ValueMesure', $measurement->getValueMesure());

		$requete->execute();
	}

	public function update(measurements $measurement) {
		$requete = $this->db->prepare('UPDATE Measurements SET IdSensor = :IdSensor, ValueMesure = :ValueMesure WHERE IdMesure = :IdMesure');

		$requete->bindValue(':IdSensor', $measurement->getIdSensor());
		$requete->bindValue(':ValueMesure', $measurement->getValueMesure());
		$requete->bindValue(':IdMesure', $measurement->getIdMesure(), PDO::PARAM_INT);

		$requete->execute();
	}

	public function delete(measurements $measurement) {
		$this->db->exec('DELETE FROM Measurements WHERE IdMesure = '. $measurement->getIdMesure());
	}

	public function listOfMeasures(sensors $sensor) {
		$requete = $this->db->prepare('SELECT * FROM Measurements WHERE IdSensor = :IdSensor');
		$requete->bindValue(':IdSensor', $sensor->getIdSensor());
		$requete->execute();
		$listOfMeasures = array();

		while($donnees = $requete->fetch()) {
			$listOfMeasures[] = new measurements(
				[
					'IdMesure' => $donnees['IdMesure'],
					'IdSensor' => $donnees['IdSensor'],
					'TimeMesure' => $donnees['TimeMesure'],
					'ValueMesure' => $donnees['ValueMesure']
				]);
		}

		return $listOfMeasures;
		$requete->closeCursor();
	}

	public function listOfTemperatureValues(cuves $cuve) {
		$sensor_manager = new sensorsManagerPDO($this->db);
		
		$liste_sensors = $sensor_manager->listOfSensors($cuve);

		$liste_mesures = array();
		$liste_values = array();

		if($liste_sensors != NULL) {
			foreach ($liste_sensors as $sensor) {
	          if ($sensor->getTypeSensor() == 'Temperature') {
	          	foreach ($this->listOfMeasures($sensor) as $mesure) {
	          		if($mesure != NULL) array_push($liste_mesures, $mesure);
	          	}
	          }
	      	}

	      	//var_dump($liste_mesures);

	      	if($liste_mesures != NULL) {
	      		$liste_values_sort = '[';
	      		$i = 0;
		      	foreach($liste_mesures as $mesure) {
	      			if($i == 0) { $liste_values_sort .= '{ time: \''.$mesure->getTimeMesure().'\', temperature: '.$mesure->getValueMesure().' }'; }
	      			else { $liste_values_sort .= ', { time: \''.$mesure->getTimeMesure().'\', temperature: '.$mesure->getValueMesure().' }'; }
	      			$i++;
	      		}
	      		$liste_values_sort .= ']';
	      		return $liste_values_sort;
	      	} else {
	      		return 'NULL NULL NULL';
	      	}
      	}
	}

	public function listOfBullesValues(cuves $cuve) {
		$sensor_manager = new sensorsManagerPDO($this->db);
		
		$liste_sensors = $sensor_manager->listOfSensors($cuve);

		$liste_mesures = array();
		$liste_values = array();

		if($liste_sensors != NULL) {
			foreach ($liste_sensors as $sensor) {
	          if ($sensor->getTypeSensor() == 'Bulles') {
	          	foreach ($this->listOfMeasures($sensor) as $mesure) {
	          		if($mesure != NULL) array_push($liste_mesures, $mesure);
	          	}
	          }
	      	}

	      	//var_dump($liste_mesures);

	      	if($liste_mesures != NULL) {
	      		$liste_values_sort = '[';
	      		$i = 0;
		      	foreach($liste_mesures as $mesure) {
	      			if($i == 0) { $liste_values_sort .= '{ time: \''.$mesure->getTimeMesure().'\', bulles: '.$mesure->getValueMesure().' }'; }
	      			else { $liste_values_sort .= ', { time: \''.$mesure->getTimeMesure().'\', bulles: '.$mesure->getValueMesure().' }'; }
	      			$i++;
	      		}
	      		$liste_values_sort .= ']';
	      		return $liste_values_sort;
	      	} else {
	      		return 'NULL NULL NULL';
	      	}
      	}
	}
}

class userstep {

	protected $erreurs = [],
			  $IdUserStep,
			  $IdAttempt,
			  $Comment,
			  $Name,
			  $IdPreviousStep,
			  $IdNextStep,
			  $Duration,
			  $TemperatureMin,
			  $TemperatureMax,
			  $StartTime,
			  $StopTime;


	// Constantes liées à la gestion des erreurs
	const USERSTEP_INVALIDE = 1;
	const ATTEMPT_INVALIDE = 2;
	const NAME_INVALIDE = 3;
	const DURATION_INVALIDE = 4;
	const NEXT_INVALIDE = 5;
	const PREV_INVALIDE = 6;

	// Constructeur et fonction d'hydratation
	public function __construct($valeurs = []) {
		if(!empty($valeurs)) {
			$this->hydrate($valeurs);
		}
	}

	public function hydrate($donnees) {
		foreach ($donnees as $attribut => $valeur) {
			$methode = 'set'.ucfirst($attribut);

			if(is_callable([$this, $methode])) {
				$this->$methode($valeur);
			}
		}
	}

	//SET
	public function setIdUserStep($IdUserStep) {
		$this->IdUserStep = (int) $IdUserStep;
	}

	public function setIdAttempt($IdAttempt) {
		$this->IdAttempt = (int) $IdAttempt;		
	}

	public function setComment($Comment) {
		$this->Comment = (string) $Comment;		
	}

	public function setName($Name) {
		$this->Name = (string) $Name;
	}

	public function setIdPreviousStep($IdPreviousStep) {
		$this->IdPreviousStep = (int) $IdPreviousStep;
	}
	
	public function setIdNextStep($IdNextStep) {
		$this->IdNextStep = (int) $IdNextStep;
	}

	public function setDuration($Duration) {
		$this->Duration = (int) $Duration;
	}

	public function setTemperatureMin($TemperatureMin) {
		$this->TemperatureMin = (float) $TemperatureMin;
	}

	public function setTemperatureMax($TemperatureMax) {
		$this->TemperatureMax = (float) $TemperatureMax;
	}

	public function setStartTime($Start) {
		$this->StartTime = $Start;
	}

	public function setStopTime($Stop) {
		$this->StopTime = $Stop;
	}

	// GET
	public function getErreurs() {
		return $this->erreurs;
	}

	public function getIdUserStep() {
		return $this->IdUserStep;
	}

	public function getIdAttempt() {
		return $this->IdAttempt;
	}

	public function getComment() {
		return $this->Comment;
	}

	public function getName() {
		return $this->Name;
	}

	public function getIdPreviousStep() {
		return $this->IdPreviousStep;
	}
	
	public function getIdNextStep() {
		return $this->IdNextStep;
	}

	public function getDuration() {
		return $this->Duration;
	}

	public function getTemperatureMin() {
		return $this->TemperatureMin;
	}

	public function getTemperatureMax() {
		return $this->TemperatureMax;
	}

	public function getStartTime() {
		return $this->StartTime;
	}

	public function getStopTime() {
		return $this->StopTime;
	}
}

class userstepManagerPDO {
	protected $db;

	public function __construct(PDO $db) {
		$this->db = $db;
	}

	public function add(userstep $step) {
		$requete = $this->db->prepare('INSERT INTO Userstep(Name, Comment, Duration, TemperatureMin, TemperatureMax, StartTime, StopTime, IdAttempt, IdPreviousStep, IdNextStep) VALUES(:Name, :Comment, :Duration, :TemperatureMin, :TemperatureMax, :StartTime, :StopTime, :IdAttempt, :IdPreviousStep, :IdNextStep)');

		$requete->bindValue(':Name', $step->getName());
		$requete->bindValue(':Comment', $step->getComment());
		$requete->bindValue(':Duration', $step->getDuration());
		$requete->bindValue(':TemperatureMin', $step->getTemperatureMin());
		$requete->bindValue(':TemperatureMax', $step->getTemperatureMax());
		$requete->bindValue(':StartTime', $step->getStartTime());
		$requete->bindValue(':StopTime', $step->getStopTime());
		$requete->bindValue(':IdAttempt', $step->getIdAttempt());
		$requete->bindValue(':IdPreviousStep', $step->getIdPreviousStep());
		$requete->bindValue(':IdNextStep', $step->getIdNextStep());

		$requete->execute();
	}

	public function update(userstep $step) {
		$requete = $this->db->prepare('UPDATE Userstep SET Name = :Name, Comment = :Comment, Duration = :Duration, TemperatureMin = :TemperatureMin, TemperatureMax = :TemperatureMax, StartTime = :StartTime, StopTime = :StopTime, IdAttempt = :IdAttempt, IdPreviousStep = :IdPreviousStep, IdNextStep = :IdNextStep  WHERE IdUserStep = :IdUserStep');

		$requete->bindValue(':IdUserStep', $step->getIdUserStep(), PDO::PARAM_INT);
		$requete->bindValue(':Name', $step->getName());
		$requete->bindValue(':Comment', $step->getComment());
		$requete->bindValue(':Duration', $step->getDuration());
		$requete->bindValue(':TemperatureMin', $step->getTemperatureMin());
		$requete->bindValue(':TemperatureMax', $step->getTemperatureMax());
		$requete->bindValue(':StartTime', $step->getStart());
		$requete->bindValue(':StopTime', $step->getStop());
		$requete->bindValue(':IdAttempt', $step->getIdAttempt());
		$requete->bindValue(':IdPreviousStep', $step->getIdPreviousStep());
		$requete->bindValue(':IdNextStep', $step->getIdNextStep());

		$requete->execute();
	}

	public function delete(userstep $step) {
		$this->db->exec('DELETE FROM Userstep WHERE IdUserStep = '. $step->getIdUserStep());
	}

	public function getUserStepFromMeasure(measurements $mesure) {
		$requete = $this->db->prepare('SELECT IdCuve FROM Sensors WHERE IdSensor = :IdSensor');
		$requete->bindValue(':IdSensor', $mesure->getIdSensor());
		$requete->execute();
		$IdCuve = $requete->fetch();

		if(!empty($IdCuve)) {

			$requete2 = $this->db->prepare('SELECT IdAttempt FROM Attempts WHERE (IdCuve = :IdCuve AND StartTime < NOW() AND StopTime > NOW())');
			$requete2->bindValue(':IdCuve', $IdCuve[0]);
			$requete2->execute();
			$IdAttempt = $requete2->fetch();

			if(!empty($IdAttempt)) {
				
				$requete3 = $this->db->prepare('SELECT * FROM UserStep WHERE (IdAttempt = :IdAttempt AND StartTime < NOW() AND StopTime > NOW())');
				$requete3->bindValue(':IdAttempt', $IdAttempt[0]);
				$requete3->execute();
				$UserStep = $requete3->fetch();
				if(!empty($UserStep)) {

					$UserStepToSend = new userstep(
					[
						'IdUserStep' => $UserStep['IdUserStep'],
						'Name' => $UserStep['Name'],
						'Comment' => $UserStep['Comment'],
						'Duration' => $UserStep['Duration'],
						'TemperatureMin' => $UserStep['TemperatureMin'],
						'TemperatureMax' => $UserStep['TemperatureMax'],
						'StartTime' => $UserStep['StartTime'],
						'StopTime' => $UserStep['StopTime'],
						'IdAttempt' => $UserStep['IdAttempt'],
						'IdPreviousStep' => $UserStep['IdPreviousStep'],
						'IdNextStep' => $UserStep['IdNextStep']
					]
					);
				return $UserStepToSend;

				} else {
					return false;
				}

			} else {
				return false;
			}

		} else {
			return false;
		}
	}

		public function getIdUserFromMeasure(measurements $mesure) {
		$requete = $this->db->prepare('SELECT IdCuve FROM Sensors WHERE IdSensor = :IdSensor');
		$requete->bindValue(':IdSensor', $mesure->getIdSensor());
		$requete->execute();
		$IdCuve = $requete->fetch();

		if(!empty($IdCuve)) {
			
			$requete2 = $this->db->prepare('SELECT IdUser FROM Cuves WHERE IdCuve = :IdCuve');
			$requete2->bindValue(':IdCuve', $IdCuve[0]);
			$requete2->execute();
			$IdUser = $requete2->fetch();

			if(!empty($IdUser)) {
				return $IdUser[0];
			} else {
				return false;
			}

		} else {
			return false;
		}
		}

		public function getUserStepsFromAttempt(attempts $essai) {
			$requete = $this->db->prepare('SELECT IdAttempt FROM Attempts WHERE IdAttempt = :IdAttempt');
			$requete->bindValue(':IdAttempt', $essai->getIdAttempt());
			$requete->execute();
			$IdAttempt = $requete->fetch();

			if(!empty($IdAttempt)) {
				
				$requete2 = $this->db->prepare('SELECT * FROM UserStep WHERE IdAttempt = :IdAttempt');
				$requete2->bindValue(':IdAttempt', $IdAttempt[0]);
				$requete2->execute();
				$ToSend = array();

				while($UserStep = $requete2->fetch()) {
					
					$ToSend[] = new userstep(
						[
							'IdUserStep' => $UserStep['IdUserStep'],
							'Name' => $UserStep['Name'],
							'Comment' => $UserStep['Comment'],
							'Duration' => $UserStep['Duration'],
							'TemperatureMin' => $UserStep['TemperatureMin'],
							'TemperatureMax' => $UserStep['TemperatureMax'],
							'StartTime' => $UserStep['StartTime'],
							'StopTime' => $UserStep['StopTime'],
							'IdAttempt' => $UserStep['IdAttempt'],
							'IdPreviousStep' => $UserStep['IdPreviousStep'],
							'IdNextStep' => $UserStep['IdNextStep']
						]);
				}
				return $ToSend;
				$requete2->closeCursor();

			} 

			else {
				return false;
				$requete2->closeCursor();
			}
		}

}

class attempts {

	protected $erreurs = [],
			  $IdAttempt,
			  $StartTime,
			  $StopTime,
			  $IdUserRecipe,
			  $Alcool,
			  $FinalVolume,
			  $Comment,
			  $IdCuve,
			  $Note;

	// Constantes liées à la gestion des erreurs
	const ATTEMPT_INVALIDE = 1;
	const USERRECIPE_INVALIDE = 2;
	const CUVE_INVALIDE = 3;
	const DURATION_INVALIDE = 4;

	// Constructeur et fonction d'hydratation
	public function __construct($valeurs = []) {
		if(!empty($valeurs)) {
			$this->hydrate($valeurs);
		}
	}

	public function hydrate($donnees) {
		foreach ($donnees as $attribut => $valeur) {
			$methode = 'set'.ucfirst($attribut);

			if(is_callable([$this, $methode])) {
				$this->$methode($valeur);
			}
		}
	}

	// SET

	public function setIdAttempt($IdAttempt) {
		$this->IdAttempt = (int) $IdAttempt;		
	}

	public function setStartTime($StartTime) {
		$this->StartTime = $StartTime;		
	}

	public function setStopTime($StopTime) {
		$this->StopTime = $StopTime;
	}

	public function setIdUserRecipe($IdUserRecipe) {
		$this->IdUserRecipe = (int) $IdUserRecipe;
	}
	
	public function setAlcool($Alcool) {
		$this->Alcool = (int) $Alcool;
	}

	public function setFinalVolume($FinalVolume) {
		$this->FinalVolume = (int) $FinalVolume;
	}

	public function setComment($Comment) {
		$this->Comment = (string) $Comment;
	}

	public function setIdCuve($IdCuve) {
		$this->IdCuve = (int) $IdCuve;
	}

	public function setNote($Note) {
		$this->Note = (int) $Note;
	}

	// GET
	public function getErreurs() {
		return $this->erreurs;
	}

	public function getIdAttempt() {
		return $this->IdAttempt;
	}

	public function getStartTime() {
		return $this->StartTime;
	}

	public function getStopTime() {
		return $this->StopTime;
	}

	public function getIdUserRecipe() {
		return $this->IdUserRecipe;
	}
	
	public function getAlcool() {
		return $this->Alcool;
	}

	public function getFinalVolume() {
		return $this->FinalVolume;
	}

	public function getComment() {
		return $this->Comment;
	}

	public function getIdCuve() {
		return $this->IdCuve;
	}

	public function getNote() {
		return $this->Note;
	}		
}

class attemptsManagerPDO {
	protected $db;

	public function __construct(PDO $db) {
		$this->db = $db;
	}

	public function add(attempts $attempt) {
		$requete = $this->db->prepare('INSERT INTO Attempts(Comment, StartTime, StopTime, IdUserRecipe, Alcool, FinalVolume, IdCuve, Note) VALUES(:Comment, :StartTime, :StopTime, :IdUserRecipe, :Alcool, :FinalVolume, :IdCuve, :Note)');

		$requete->bindValue(':Comment', $attempt->getComment());
		$requete->bindValue(':StartTime', $attempt->getStartTime());
		$requete->bindValue(':StopTime', $attempt->getStopTime());
		$requete->bindValue(':IdUserRecipe', $attempt->getIdUserRecipe());
		$requete->bindValue(':Alcool', $attempt->getAlcool());
		$requete->bindValue(':FinalVolume', $attempt>getFinalVolume());
		$requete->bindValue(':IdCuve', $attempt>getIdCuve());
		$requete->bindValue(':Note', $attempt>getNote());

		$requete->execute();
	}

	public function update(attempts $attempt) {
		$requete = $this->db->prepare('UPDATE Attempts SET Comment = :Comment, StartTime = :StartTime, StopTime = :StopTime, IdUserRecipe = :IdUserRecipe, Alcool = :Alcool, FinalVolume = :FinalVolume, IdCuve = :IdCuve, Note = :Note WHERE IdAttempt = :IdAttempt');

		$requete->bindValue(':Comment', $attempt->getComment());
		$requete->bindValue(':StartTime', $attempt->getStartTime());
		$requete->bindValue(':StopTime', $attempt->getStopTime());
		$requete->bindValue(':IdUserRecipe', $attempt->getIdUserRecipe());
		$requete->bindValue(':Alcool', $attempt->getAlcool());
		$requete->bindValue(':FinalVolume', $attempt>getFinalVolume());
		$requete->bindValue(':IdCuve', $attempt>getIdCuve());
		$requete->bindValue(':Note', $attempt>getNote());

		$requete->execute();
	}

	public function delete(attempts $attempt) {
		$this->db->exec('DELETE FROM Attempts WHERE IdAttempt = '. $attempt->getIdAttempt());
	}

	public function listOfAttempts(userRecipe $recipe) {
		$requete = $this->db->prepare('SELECT * FROM Attempts WHERE IdUserRecipe = :IdUserRecipe');
		$requete->bindValue(':IdUserRecipe', $recipe->getIdUserRecipe());
		$requete->execute();
		$listRecipe = array();

		while($donnees = $requete->fetch()) {
			$listAttempts[] = new attempts(
				[
					'IdAttempt' => $donnees['IdAttempt'],
					'StartTime' => $donnees['StartTime'],
					'StopTime' => $donnees['StopTime'],
					'IdUserRecipe' => $donnees['IdUserRecipe'],
					'Alcool' => $donnees['Alcool'],
					'FinalVolume' => $donnees['FinalVolume'],
					'Comment' => $donnees['Comment'],
					'IdCuve' => $donnees['IdCuve'],					
					'Note' => $donnees['Note']
				]);
		}

		return $listAttempts;
		$requete->closeCursor();
	}
}

class userRecipe {

	protected $erreurs = [],
			  $IdUserRecipe,
			  $RecipeName,
			  $Comment,
			  $Ingredients,
			  $Informations,
			  $IdUser;


	// Constantes liées à la gestion des erreurs
	const RECIPE_INVALIDE = 1;
	const USER_INVALIDE = 2;
	const INGREDIENTS_INVALIDE = 3;

	// Constructeur et fonction d'hydratation
	public function __construct($valeurs = []) {
		if(!empty($valeurs)) {
			$this->hydrate($valeurs);
		}
	}

	public function hydrate($donnees) {
		foreach ($donnees as $attribut => $valeur) {
			$methode = 'set'.ucfirst($attribut);

			if(is_callable([$this, $methode])) {
				$this->$methode($valeur);
			}
		}
	}

	// SETTERS
	public function setIdUserRecipe($IdUserRecipe) {
		$this->IdUserRecipe = (int) $IdUserRecipe;
	}

	public function setRecipeName($RecipeName) {
		$this->RecipeName = (string) $RecipeName;		
	}

	public function setComment($Comment) {
		$this->Comment = (string) $Comment;		
	}

	public function setIngredients($Ingredients) {
		$this->Ingredients = (string) $Ingredients;
	}

	public function setInformations($Informations) {
		$this->Informations = (string) $Informations;
	}

	public function setIdUser($IdUser) {
		$this->IdUser = (int) $IdUser;
	}

	// GETTERS
	public function getErreurs() {
		return $this->erreurs;
	}
	public function getIdUserRecipe() {
		return $this->IdUserRecipe;
	}

	public function getRecipeName() {
		return $this->RecipeName;
	}

	public function getComment() {
		return $this->Comment;
	}

	public function getIngredients() {
		return $this->Ingredients;
	}

	public function getInformations() {
		return $this->Informations;
	}

	public function getIdUser() {
		return $this->IdUser;
	}
}

class userRecipeManagerPDO {
	protected $db;

	public function __construct(PDO $db) {
		$this->db = $db;
	}

	public function add(userRecipe $recipe) {
		$requete = $this->db->prepare('INSERT INTO UserRecipe(RecipeName, Comment, Ingredients, Informations, IdUser) VALUES(:RecipeName, Comment, Ingredients, Informations, IdUser)');

		$requete->bindValue(':RecipeName', $recipe->getRecipeName());
		$requete->bindValue(':Comment', $recipe->getComment());
		$requete->bindValue(':Ingredients', $recipe->getIngredients());
		$requete->bindValue(':Informations', $recipe->getInformations());
		$requete->bindValue(':IdUser', $recipe->getIdUser());

		$requete->execute();
	}

	public function update(userRecipe $recipe) {
		$requete = $this->db->prepare('UPDATE UserRecipe SET RecipeName = :RecipeName, Comment = :Comment, Ingredients = :Ingredients, Informations = :Informations, IdUser = :IdUser WHERE IdUserRecipe = :IdUserRecipe');

		$requete->bindValue(':RecipeName', $recipe->getRecipeName());
		$requete->bindValue(':Comment', $recipe->getComment());
		$requete->bindValue(':Ingredients', $recipe->getIngredients());
		$requete->bindValue(':Informations', $recipe->getInformations());
		$requete->bindValue(':IdUser', $recipe->getIdUser());

		$requete->execute();
	}

	public function delete(userRecipe $recipe) {
		$this->db->exec('DELETE FROM UserRecipe WHERE IdUserRecipe = '. $recipe->getIdUserRecipe());
	}

	public function listOfUserRecipe(users $user) {
		$requete = $this->db->prepare('SELECT * FROM UserRecipe WHERE IdUser = :IdUser');
		$requete->bindValue(':IdUser', $user->getIdUser());
		$requete->execute();
		$listRecipe = array();

		while($donnees = $requete->fetch()) {
			$listRecipe[] = new userRecipe(
				[
					'IdUserRecipe' => $donnees['IdUserRecipe'],
					'RecipeName' => $donnees['RecipeName'],
					'Comment' => $donnees['Comment'],
					'Ingredients' => $donnees['Ingredients'],
					'Informations' => $donnees['Informations'],
					'IdUser' => $donnees['IdUser']
				]);
		}

		return $listRecipe;
		$requete->closeCursor();
	}

	public function getRecipeFromAttempt(attempts $try) {
		$requete = $this->db->prepare('SELECT IdUserRecipe FROM Attempts WHERE IdAttempt = :IdAttempt');
		$requete->bindValue(':IdAttempt', $try->getIdAttempt());
		$requete->execute();
		$Id_rcp = $requete->fetch();

		if(!empty($Id_rcp)) {
			
			$requete2 = $this->db->prepare('SELECT * FROM UserRecipe WHERE IdUserRecipe = :IdUserRecipe');
			$requete2->bindValue(':IdUserRecipe', $Id_rcp[0]);
			$requete2->execute();
			$Recipe = $requete2->fetch();

			if(!empty($Id_rcp)) {
				$recette = new userRecipe(
				[
					'IdUserRecipe' => $Recipe['IdUserRecipe'],
					'RecipeName' => $Recipe['RecipeName'],
					'Comment' => $Recipe['Comment'],
					'Ingredients' => $Recipe['Ingredients'],
					'Informations' => $Recipe['Informations'],
					'IdUser' => $Recipe['IdUser']
				]);
				return $recette;
			} 
			else {
				return false;
			}

		} else {
			return false;
		}
	}
}

?>
