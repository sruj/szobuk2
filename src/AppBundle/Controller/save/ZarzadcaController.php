<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\ZamowienieType;
use AppBundle\Form\ZamowienieListType;
use AppBundle\Entity\Zamowienie;
use AppBundle\Entity\ZamowienieList;
use AppBundle\Entity\Status;
use AppBundle\Form\Filter\StatusType;
use AppBundle\Form\Filter\DataZamType;
use AppBundle\Form\Filter\NrKlientaType;


/**
 * Kategoria controller.
 *
 * @Route("/zarzadca")
 */
class ZarzadcaController extends Controller
{
//     * @Route("/panel/{sortArr}-{orderBy}-{query}-{EntFldName}", name="panelSort")
    //@Method("GET")
    /**
     * @Route("/panel/{sortArr}/{orderBy}/{query}/{EntFldName}", name="panelSort")
     * @Template()
     */
    public function panelsortAction(Request $request,$sortArr=false, $orderBy='idzamowienie', $query=false,$EntFldName=false)
    {
        //[-ASC DESC sortowanie-]
        //Jeśli pierwszy raz otwieram stronę to tworzę tablicę $sortArr
        //i każdy element ustawiam na 'null' poza Numer=ASC
        //else
        //tworzę tablicę $sortArr każdy element ustawiam na 'null' 
        //poza klikniętym który zmieniam ASC na DESC i odwrotnie    .
/*1*/        if(!($sortArr)){$sortArr=$this->setAllNullNumerASC();}
        else{
        $sortArr = $this->setSortVar($sortArr);  
        $sortArr = $this->AscDescChanger($sortArr,$orderBy);        
        }

        //[-Formularze-] Tworzę formularze o strukturze XxxType. Bez zawartości.
        $StatusForm = $this->createForm(new StatusType());
        $DataZamForm = $this->createForm(new DataZamType());
        $NrKlientaForm = $this->createForm(new NrKlientaType());

        //[-Formularze-]Jeśli wypełniłem formularz to odbieram zawartość
        $StatusForm->handleRequest($request);
        $DataZamForm->handleRequest($request);
        $NrKlientaForm->handleRequest($request);
                
        //kasuję kilka zmiennych bo nie wiedzieć czemu po wybraniu filtra i zmianie sortowania
        //nie reaguje na kolejne filtry
        if(($StatusForm->isValid())or($DataZamForm->isValid())or($NrKlientaForm->isValid())){
            $EntFldName=false;
            $query=false;
        }
        
        //[-Filtr-]Sprawdza czy kliknięty był filtr. 
        //$findBy = 'status' or 'data' or 'klient' or 'all'
        $findBy = $this->findByWhat($StatusForm,$DataZamForm,$NrKlientaForm,$EntFldName);
        
        //[-Filtr-]Jeśli przechodzę z filtra do filtra to kasuję query
        if(!($EntFldName)){$query=false;}
          echo '<pre><br/><br/><br/><br/><br/>',print_r($findBy),'</pre>'; 
          echo '<pre>',print_r($query),'</pre>'; 
          echo '<pre>',print_r($EntFldName),'</pre>'; 
        //[-Repozytorium-][-Sortowanie-]Najważniejsze. Tworzę repozytorium $zamowienia (wykorzystywane do
        //utworzenia tabeli i formularzy panelu) i ustawiam elementy
        //tablicy $sortArr.  
        if($findBy == 'all'){ 
            $temp = $this->zamowienieRepositoryMakerAndSortArrChangerForAll($orderBy, $sortArr);
            $sortArr = $temp['sortArr'];
            $zamowienia = $temp['zamowienia'];
        }elseif($findBy == 'idstatus'){
            //jeśli wcześniej użyłem filtrowania a teraz tylko kliknięto zmianę sortowania to 
            //odbieram poprzednie dane o filtrze w przeciwnym razie odbieram dane formularza filtra
            if(!($query)){
                $Identifier = $StatusForm->get('status')->getData();
                $Identifier = $Identifier->getIdstatus();
                $query = 'idstatus = '.$Identifier;
            }
            $EntFldName = 'idstatus';
/*2*/            $temp = $this->zamowienieRepositoryMakerAndSortArrChangerNotForAll(
                    $query ,$sortArr, $orderBy);
            $sortArr = $temp['sortArr'];
            $zamowienia = $temp['zamowienia'];
        }
        elseif($findBy == 'data'){
            if(!($query)){
                $od = $DataZamForm->get('od')->getData()->format('Y-m-d H:i:s');
                $do = $DataZamForm->get('do')->getData()->format('Y-m-d H:i:s');
                $query = "datazlozenia BETWEEN '".$od."' AND '".$do."'"; 
            }  else {
                $query = urldecode($query);
                
            }
            $EntFldName = 'data';
            $temp = $this->zamowienieRepositoryMakerAndSortArrChangerNotForAll(
                    $query ,$sortArr, $orderBy);
            $sortArr = $temp['sortArr'];
            $zamowienia = $temp['zamowienia'];            
            $query=  urlencode($query);
            
        }elseif($findBy == 'klient'){
            if(!($query)){
//                          echo '<pre>',print_r($StatusForm),'</pre>'; 
                $Identifier = $NrKlientaForm->get('idklient')->getData();
//                          echo '<pre>',print_r($Identifier),'</pre>'; 
                $Identifier = $Identifier->getIdklient();                
                $query = 'idklient = '.$Identifier;
            }
            $EntFldName = 'idklient';
/*2*/            $temp = $this->zamowienieRepositoryMakerAndSortArrChangerNotForAll(
                    $query ,$sortArr, $orderBy);
            $sortArr = $temp['sortArr'];
            $zamowienia = $temp['zamowienia'];
        }
        
        //[-Repozytorium II-]Tworzy zmienną $zamowieniaProdukty wysyłaną do Twiga. Wykorzystywane 
        //tam do obliczenia sumy i ilości produktów dla każdego zamówienia.
        $zamowieniaProdukty= $this->getDoctrine()
            ->getRepository('AppBundle:ZamowienieProdukt')
            ->findall();

        //[-Formularz Główny-]Ładowanie $zamowieniaList - zmiennej potrzebnej do głównego formularza. 
        //To kluczowa zmienna. Obiekt ZamowienieList() to kolekcja formularzy
        //pozwala na stworzenie wielu formularzy z jednym buttonem
        $zamowieniaList = new ZamowienieList();     
        foreach ($zamowienia as $zamowienie) {
            $zamowieniaList->getZamowienia()->add($zamowienie);
        }  

        //[-Formularz Główny-]Główny formularz $form. Struktura to kolekcja formularzy ZamowienieListType()
        //a zawartość to $zamowieniaList
        $form = $this->createForm(new ZamowienieListType(), $zamowieniaList);

        //[-Formularz Główny-]Jeśli w panelu zmienionio jakiś status i kliknięto zapisz to odbieram 
        //zawartość formularza i aktualizuję bazę danych
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($form->getData() as $task) {
                $em->merge($task);
            }
            $em->flush();
        }        
        
        return array('zamowieniaProdukty'=>$zamowieniaProdukty, 
            'sortArr' => $sortArr,
            'form' => $form->createView(),
            'EntFldName' => $EntFldName, 
            'query' => $query,
            'StatusForm' => $StatusForm->createView(),
            'DataZamForm' => $DataZamForm->createView(),
            'NrKlientaForm' => $NrKlientaForm->createView(),
        );
    }
    
    
    
    
    
    
    //[-Sortowanie-]Jeśli pierwszy raz otwieram stronę to tworzę tablicę $sortArr
    //i każdy element ustawiam na DESC
    public function setAllNullNumerASC(){
        $sortArr = array('Data'=>'null','Klient'=>'null','Numer'=>'ASC', 
            'Status'=>'null');
        return $sortArr;
    }
    
    //[-Sortowanie-]zmiana każdej sortownicy na przeciwną (w kolejnej funkcji zależnie od wartości 
    //klikniętej zmiennej click te zmienne się zmienią.     
    public function setSortVar($var){
        if ($var=='ASC'){
            $var='DESC';
        }else{
            $var='ASC';
        }
    
        return $var;
    }
    
    //[-Sortowanie-]W zależności od wartości zmiennej $OrderBy ustawia elementy tablicy
    //$sortArr na 'null' poza elementem tablicy tożsamym z $OrderBy.
    //Czyli jeśli $OrderBy=='datazlozenia' to ustawi $sortArr['Data']
    public function AscDescChanger($Sort,$OrderBy){
        switch ($OrderBy) {
            case "datazlozenia":
                $sortArr = array('Status'=>'null','Klient'=>'null','Numer'=>'null', 
                    'Data'=>$Sort);
                return $sortArr;
                break;
            case "idklient":
                $sortArr = array('Status'=>'null','Data'=>'null','Numer'=>'null', 
                    'Klient'=>$Sort);
                return $sortArr;
                break;
            case "idstatus":
                $sortArr = array('Data'=>'null','Klient'=>'null','Numer'=>'null', 
                    'Status'=>$Sort);
                return $sortArr;
                break;
            case "idzamowienie":
                $sortArr = array('Status'=>'null','Klient'=>'null','Data'=>'null', 
                    'Numer'=>$Sort);
                return $sortArr;
                break;
            default:
                $sortArr = array('Status'=>'null','Klient'=>'null','Data'=>'null', 
                    'Numer'=>'ASC');
                return $sortArr;
        }    
    }

    //[-Repozutorium-][-Sortowanie-]Tworzy repozytorium w zmiennej $zamowienia. Dodatkowo ustawia elementy
    //tablicy $sortArr. W zależności o wartości 
    public function zamowienieRepositoryMakerAndSortArrChangerForAll($orderBy, $sortArr){
        $zamowienia = $this->getDoctrine()->getRepository('AppBundle:Zamowienie')
                ->findAllOrderedByY($sortArr,$orderBy); 

        return array('sortArr'=>$sortArr,'zamowienia'=>$zamowienia);
    } 

    //[-Repozutorium-][-Sortowanie-]Tworzy repozytorium w zmiennej $zamowienia. Dodatkowo ustawia elementy
    //tablicy $sortArr. W zależności o wartości 
/*3*/    public function zamowienieRepositoryMakerAndSortArrChangerNotForAll($query,$sortArr, $orderBy){
        $zamowienia = $this->getDoctrine()->getRepository('AppBundle:Zamowienie')
/*4 tu przypisuje ASC*/                ->findByXOrderedByY($query,$sortArr,$orderBy);
//        $sortArr = AscDescChanger($sortArr,$orderBy);

        return array('sortArr'=>$sortArr,'zamowienia'=>$zamowienia);
    } 
    
    //[-Filtr-]sprawdzam jak pobrać z bazy repozytory i ustawiam zmienną $findBy. 
    //(Czy było klikniete sortowanie czy filtrowanie)
    public function findByWhat($StatusForm,$DataZamForm,$NrKlientaForm,$EntFldName){
        if(!($EntFldName)){
            if($StatusForm->isValid()){ $findBy='idstatus';}
            elseif($DataZamForm->isValid()){ $findBy='data';}
            elseif($NrKlientaForm->isValid()){ $findBy='klient';}
            else{ $findBy='all';}
        }else{$findBy=$EntFldName;}

        return $findBy;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
   
    /**
    * @Route("/test88", name="test88")
    * @Template()
    */
   public function test88Action()
   { 

       
        return array(  );    
   } 
    
    /**
     * test.
     * @Route("/test15",name="test15")
     * @Method("GET")
     * @Template()
     */
    public function test15Action()
    {


        return array();
    }    
    /**
     * test.
     * @Route("/test16/{clicked}", defaults={"clicked" = 1},name="test16")
     * @Method("GET")
     * @Template()
     */
    public function test16Action($clicked)
    {

//        $DataSort=true;
//        if ($clicked==!'null'){
//            $DataSort=!$DataSort;
//        }
    echo '<pre>',print_r($clicked),'</pre>'; 
//    echo '<pre>',print_r($DataSort),'</pre>'; 
        return array(
            'clicked'=>$clicked);
    }    
}