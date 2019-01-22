#include <SoftwareSerial.h>

SoftwareSerial ESP8266(10, 11);

String NomReseauWifi = "Clement"; // Garder les guillements
String MotDePasse      = "siaf9622"; // Garder les guillements
float valTemperature[3] = {NULL, NULL, NULL};

//String idSensor[3] = {"3", "4", "5"}; // compte invite
//String idBubble = "6"; // compte invite

String idSensor[3] = {"1", "7", "10"}; // compte Amaury
String idBubble = "2"; // compte Amaury

int connexionWifi = 0; // permet de gérer la connexion au réseau Wifi
int keepGoing = 1; // variable permettant de gérer les périodes d'envoi de données

#define PERIODICITE_CAPTEURS_HIGH 30000 // toutes les 1 minutes = 60 000 ms
#define PERIODICITE_CAPTEURS_LOW 60000 // tous les quarts d'heure = 60 000 * 15 ms = 900 000

// Définition des constantes pour les capteurs de température
#define PIN_TEMPERATURE_DATA_1 A0
#define PIN_TEMPERATURE_DATA_2 A1
#define PIN_TEMPERATURE_DATA_3 A2
#define PIN_TEMPERATURE_ONOFF 2
#define PIN_COMPTEUR_BULLE 3
#define PIN_PLAQUE_CHAUFFANTE 4
#define LED_PLAQUE_CHAUFFANTE 5
#define PIN_PELTIER 6
#define LED_PELTIER 7

#define THERMISTORNOMINAL 10000 // resistance at 25 degrees C
#define TEMPERATURENOMINAL 25 // temp. for nominal resistance (almost always 25 C)
#define NUMSAMPLES 5 // how many samples to take and average, more takes longer but is more 'smooth'
#define BCOEFFICIENT 3977 // The beta coefficient of the thermistor (usually 3000-4000)
#define SERIESRESISTOR 10000 // the value of the 'other' resistor
uint16_t samples0[NUMSAMPLES];
uint16_t samples1[NUMSAMPLES];
uint16_t samples2[NUMSAMPLES];
uint16_t countBubbles = 0;
uint16_t bubblesPerMin = 0;
unsigned long time_begin;
unsigned long time_end;


/****************************************************************/
/*                             INIT                             */
/****************************************************************/
void setup()
{
  Serial.begin(115200);
  ESP8266.begin(115200);
  initESP8266();
  pinMode(PIN_TEMPERATURE_ONOFF, OUTPUT);
  pinMode(PIN_PLAQUE_CHAUFFANTE, OUTPUT);
  pinMode(LED_PLAQUE_CHAUFFANTE, OUTPUT);
  pinMode(PIN_PELTIER, OUTPUT);
  pinMode(LED_PELTIER, OUTPUT);
  pinMode(PIN_COMPTEUR_BULLE, INPUT_PULLUP);
  attachInterrupt(digitalPinToInterrupt(PIN_COMPTEUR_BULLE), countBubblesInterrupt, RISING);
  
  time_begin = millis();
  digitalWrite(PIN_PLAQUE_CHAUFFANTE, HIGH);
  digitalWrite(LED_PLAQUE_CHAUFFANTE, LOW);
  digitalWrite(PIN_PELTIER, HIGH);
  digitalWrite(LED_PELTIER, LOW);
}
/****************************************************************/
/*                        BOUCLE INFINIE                        */
/****************************************************************/
void loop()
{
  
  if(keepGoing == 1) {
    recupererValTemperature();
    time_end = millis();
    bubblesPerMin = countBubbles / (time_end - time_begin);
    time_begin = time_end;
    countBubbles = 0;
    
    envoieAuESP8266("AT+CIPCLOSE=2");
    recoitDuESP8266(1000);
    
    envoieAuESP8266("AT+CIPSTART=2,\"TCP\",\"90.89.213.17\",80");
    recoitDuESP8266(1000);

    envoieAuESP8266("AT+CIPSEND=2,116");
    recoitDuESP8266(500);
    
    envoieAuESP8266("GET /testServeur3/testCapteur.php?idBubbleSensor="+idBubble+"&bubblesPerMinute="+bubblesPerMin+" HTTP/1.1\r\nHost: 90.89.213.17\r\n\r\n");
    recupererCommandeESP8266(2000);
      
    envoieAuESP8266("AT+CIPSEND=2,4");
    recupererCommandeESP8266(500);
    envoieAuESP8266("TEST");
    recupererCommandeESP8266(500);
    
    envoieAuESP8266("AT+CIPCLOSE=2");
    recoitDuESP8266(3000);
    delay(2000);

    envoieAuESP8266("AT+CIPSTART=2,\"TCP\",\"90.89.213.17\",80");
    recoitDuESP8266(1000);
    
    envoieAuESP8266("AT+CIPSEND=2,159");
    recoitDuESP8266(500);
    
    envoieAuESP8266("GET /testServeur3/testCapteur.php?idSensor1="+idSensor[0]+"&valMesure1="+valTemperature[0]+"&idSensor2="+idSensor[1]+"&valMesure2="+valTemperature[1]+"&idSensor3="+idSensor[2]+"&valMesure3="+valTemperature[2]+" HTTP/1.1\r\nHost: 90.89.213.17\r\n\r\n");
    recupererCommandeESP8266(500);

    keepGoing = 0;
  }

  envoieAuESP8266("AT+CIPSEND=2,4");
  recupererCommandeESP8266(500);
  envoieAuESP8266("TEST");
  recupererCommandeESP8266(500);
}

/****************************************************************/
/*                Fonction qui initialise l'ESP8266             */
/****************************************************************/
void initESP8266()
{
  Serial.println("**************** DEBUT DE L'INITIALISATION ***************");
  envoieAuESP8266("AT+RST");
  recoitDuESP8266(100);
  //Serial.println("************* CHANGEMENT DE BAUD RATE A 9600 *************");
  envoieAuESP8266("AT+UART_DEF=9600,8,1,0,0");
  recoitDuESP8266(100);
  ESP8266.begin(9600);
  //Serial.println("*************** MISE EN AP + STATION MODE ****************");
  envoieAuESP8266("AT+CWMODE=3");
  recoitDuESP8266(100);
  /*
  Serial.println("****************** LISTE DES POINTS D'ACCES **************");
  envoieAuESP8266("AT+CWLAP");
  recoitDuESP8266(1000);
  */
  //Serial.println("************ ACTIVATION DES MULTI-CONNEXIONS *************");
  envoieAuESP8266("AT+CIPMUX=1");   
  recoitDuESP8266(100);
  //Serial.println("************ ACTIVATION DU SERVEUR WEB *************");
  /*envoieAuESP8266("AT+CIPSERVER=1,80");   
  recoitDuESP8266(100);*/
  //Serial.println("************ CHANGEMENT DE L'@ IP A 192.168.61.179 *************");
  /*envoieAuESP8266("AT+CIPAP=\"192.168.61.179\"");   
  recoitDuESP8266(100);*/
  //Serial.println("************ AFFICHAGE ADRESSE IP *************");
  /*envoieAuESP8266("AT+CIFSR");   
  recoitDuESP8266(1000);*/
  
  //Serial.println("*************** CONNEXION AU RESEAU WIFI *****************");
  envoieAuESP8266("AT+CWJAP=\""+ NomReseauWifi + "\",\"" + MotDePasse +"\"");
  recoitDuESP8266(1000);
  
  Serial.println("***************** INITIALISATION TERMINEE ****************");
  Serial.println("");
  recoitDuESP8266(5000);
}

/****************************************************************/
/*       Fonction qui envoie une commande AT à l'ESP8266        */
/****************************************************************/
void envoieAuESP8266(String commande)
{
  ESP8266.println(commande);
}

/****************************************************************/
/*Fonction qui lit et affiche les messages envoyés par l'ESP8266*/
/****************************************************************/
void recoitDuESP8266(const int timeout)
{
  String reponse = "";
  long int time = millis();
  while( (time+timeout) > millis())
  {
    while(ESP8266.available())
    {
      char c = ESP8266.read();
      reponse+=c;
    }
  }
  Serial.print(reponse);   
}

/***********************************************************************/
/*   Fonction qui détecte le SSID et PASSWORD WIFI d'une requête GET   */
/*Accéder à l'adresse 192.168.61.179?ssid=VOTRE_SSII&mdp=VOTRE_PASSWORD*/
/***********************************************************************/
/*void detecterCodesWifi(const int timeout)
{
  String reponse = "";
  long int time = millis();
  while( (time+timeout) > millis())
  {
    while(ESP8266.available())
    {
      char c = ESP8266.read();
      reponse+=c;
    }
  }
  
  Serial.print(reponse);
  int indexDebut = 6 + reponse.indexOf("GET /?");
  int indexFin = reponse.indexOf(" HTTP/");
  String reponseCoupe = reponse.substring(indexDebut, indexFin);
  int indexDebutSSII = 1+reponseCoupe.indexOf("=");
  int indexFinSSII = reponseCoupe.indexOf("&");
  int indexDebutPASS = 1+reponseCoupe.indexOf("=", indexDebutSSII+1);
  int indexFinPASS = indexFin;
  Serial.print("SSID : ");
  if(NomReseauWifi == "") {
    NomReseauWifi = reponseCoupe.substring(indexDebutSSII, indexFinSSII);
  }
  Serial.print(NomReseauWifi);
  Serial.print("---- MDP : ");
  if(MotDePasse == "") {
    MotDePasse = reponseCoupe.substring(indexDebutPASS, indexFinPASS);
  }
  Serial.println(MotDePasse);
  
  Serial.println("*************** CONNEXION AU RESEAU WIFI *****************");
  envoieAuESP8266("AT+CWJAP=\""+ NomReseauWifi + "\",\"" + MotDePasse +"\"");
  recoitDuESP8266(2000);
}*/


/* 
 *  Le retour est dans le body de la réponse HTTP GET
 *  Sous le format : "BREWDYCOMMANDERETOUR:COMMANDE_RETOUR"
 *  Où COMMANDE_RETOUR est égal à 0 (NE RIEN FAIRE), 1 (CHAUFFE) ou 2 (REFROIDIR)
 *  
 *  Récupérer la commande du serveur
 *  0 pour "NE RIEN FAIRE"
 *  1 pour "CHAUFFE"
 *  2 pour "REFROIDIR"
*/
void recupererCommandeESP8266(const int timeout)
{
  String reponse = "";
  long int time = millis();
  while( (time+(timeout)) > millis())
  {
    while(ESP8266.available())
    {
      char c = ESP8266.read();
      reponse+=c;
    }
  }
  
  Serial.print(reponse);
  int indexDebut = 21 + reponse.indexOf("BREWDYCOMMANDERETOUR:");
  int indexFin = indexDebut + 1;
  String commande = "2";
  commande = reponse.substring(indexDebut, indexFin);
  if(commande == "0" || commande == "1" || commande == "2") {
    Serial.print("-------- RECEPTION COMMANDE --------");
    if(commande == "2") {
      Serial.println("NE RIEN FAIRE");
      digitalWrite(PIN_PLAQUE_CHAUFFANTE, HIGH);
      digitalWrite(LED_PLAQUE_CHAUFFANTE, LOW);
      digitalWrite(PIN_PELTIER, HIGH);
      digitalWrite(LED_PELTIER, LOW);
      delay(PERIODICITE_CAPTEURS_HIGH);
    }
    else if(commande == "0") {
      Serial.println("EN TRAIN DE CHAUFFER");
      digitalWrite(PIN_PLAQUE_CHAUFFANTE, LOW);
      digitalWrite(LED_PLAQUE_CHAUFFANTE, HIGH);
      digitalWrite(PIN_PELTIER, HIGH);
      digitalWrite(LED_PELTIER, LOW);
      delay(PERIODICITE_CAPTEURS_LOW);
    }
    else if(commande == "1") {
      Serial.println("EN TRAIN DE REFROIDIR");
      digitalWrite(PIN_PLAQUE_CHAUFFANTE, HIGH);
      digitalWrite(LED_PLAQUE_CHAUFFANTE, LOW);
      digitalWrite(PIN_PELTIER, LOW);
      digitalWrite(LED_PELTIER, HIGH);
      delay(PERIODICITE_CAPTEURS_HIGH);
    }
    Serial.println("-------- FIN RECEPTION COMMANDE --------");
    keepGoing = 1;
  } else {
    delay(3000);
    keepGoing = 1;
  }
}

/*******************************************************************/
/* Fonction qui récupère les valeurs de température des 3 capteurs */
/*******************************************************************/
void recupererValTemperature()
{
  uint8_t i;
  uint8_t j;
  float average;

  // mettre le capteur sous tension le temps de la mesure
  digitalWrite(PIN_TEMPERATURE_ONOFF, HIGH);
  // récupérer les NUMSAMPLES valeurs sur chacun des 3 capteurs de température
  for (i=0 ; i < NUMSAMPLES ; i++) {
    samples0[i] = analogRead(PIN_TEMPERATURE_DATA_1);
    samples1[i] = analogRead(PIN_TEMPERATURE_DATA_2);
    samples2[i] = analogRead(PIN_TEMPERATURE_DATA_3);
    delay(10);
  }
  
  // mettre à 0 la tension aux bornes du capteur (économie d'énergie)
  digitalWrite(PIN_TEMPERATURE_ONOFF, LOW);

  for(j=0 ; j<3 ; j++) {

    // Moyenne des 5 valeurs récupérées
    average = 0;
    for (i=0; i< NUMSAMPLES; i++) {
      if(j==0) average += samples0[i];
      else if(j==1) average += samples1[i];
      else if(j==2) average += samples2[i];
    }
    average /= NUMSAMPLES;
  
    // Convertir la valeur en résistance
    average = 1023 / average - 1;
    average = SERIESRESISTOR / average;

    // Convertir la valeur en °C
    float steinhart;
    steinhart = average / THERMISTORNOMINAL;  // (R/Ro)
    steinhart = log(steinhart);               // ln(R/Ro)
    steinhart /= BCOEFFICIENT;                // 1/B * ln(R/Ro)
    steinhart += 1.0 / (TEMPERATURENOMINAL + 273.15); // + (1/To)
    steinhart = 1.0 / steinhart;              // Invert
    steinhart -= 273.15;                      // convert to C

    valTemperature[j] = steinhart;
  }

  for(j=0 ; j<3 ; j++) {
    Serial.print("Capteur ");
    Serial.print(idSensor[j]);
    Serial.print(" : ");
    Serial.print(valTemperature[j]);
    Serial.println(" °C");
  }
  
}

/*******************************************************************/
/*           Interruption calculant le nombre de bulles            */
/*******************************************************************/
void countBubblesInterrupt() {
  countBubbles++;
}
