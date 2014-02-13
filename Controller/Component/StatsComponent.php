<?php

App::import('Component', 'Component');

/**
 * Parámetros de 
 *
 * @package default
 * @author Alfonso Etxeberria
 */

class StatsComponent extends Component 
{
  
/**
 * El key del query que determina la fecha de inicio en las peticiones get
 *
 * @var string
 */
  private $startQuery = 'from';
  
/**
 * El key del query que determina la fecha final en las peticiones get
 *
 * @var string
 */
  private $endQuery = 'to';
  
  
  private $fromQuery = null;
  
  private $toQuery = null;
  
  private $requestTimeKey = 1;
  
/**
 * El intervalo de fecha mostrado por defecto, que será desde la fecha de hoy restado a lo indicado en esta variable
 *
 * @var string
 */
  private $defaultInterval = '-1 month';
  
/**
* El key del query que determina el tipo de petición
 *
 * @var string
 */
  private $typeQuery = 'type';
  
/**
 * Los tipos de peticiones posibles
 *
 * @var array
 */
  private $queryTypes = array(
      'hour' => array(
          'HOUR'
      ),
      'date' => array(
          'DATE'
      ),
      'dayofweek' => array(
          'DAYOFWEEK'
      ),
      'month' => array(
          'YEAR',
          'MONTH'
      )
  );
  
  
/**
 * El tipo de petición por defecto
 *
 * @var string
 */
  private $defaultQueryType = 'date';
  
  
  
  public function initialize( &$controller)
  {
    $this->Controller = $controller;
    $this->Request = $controller->request;
    $this->setDateQueries();
  }
  
  
  
/**
 * Debido a que Cake retorna con el key 0 los datos pedidos para peticiones de tipo COUNT(*), 
 * este método retorna tan solo la clave 0 para cada resultado, limpiando así el array
 *
 * @param array $results 
 * @return array
 * @since Shokesu 0.1
 */
  public function build( $results)
  {
    $return = array();
    $total = 0;
    
    foreach( $results as $key => $result)
    {
      $return [] = $result [0];
    };
    
    $return = $this->setDates( $return);
    
    if( isset( $this->Request->query ['absolute']))
    {
      foreach( $return as $key => $result)
      {
        $total = $total + (int)$result ['number'];
        $return [$key]['number'] = $total;
      }
      
    }
    return $return;
  }
  
  public function buildPie( $results)
  {
    $return = array();
    $model = key( $results [0]);
    $key = key( current( $results [0]));

    foreach( $results as $result)
    {
      $return [] = array(
          $result [$model][$key],
          (int)$result [0]['number']
      );
    }
    
    return $return;
  }
  
  public function setDateQueries()
  {
    if( !isset( $this->Request->query ['dates']))
    {
      $dates = date( 'd/m/Y', strtotime( $this->defaultInterval)). ' - ' . date( 'd/m/Y');
    }
    else
    {
      $dates = $this->Request->query ['dates'];
    }
    
    list( $from, $to) = explode( ' - ', $dates);
    
    list( $day, $month, $year) = explode( '/', $from);
    $this->fromQuery = "$year-$month-$day";
    
    list( $day, $month, $year) = explode( '/', $to);
    $this->toQuery = "$year-$month-$day"; 
    
  }
  
  public function setRequestTimeKey( $key)
  {
    $this->requestTimeKey = $key;
  }
  
  public function dateKey( $type)
  {
    return $this->$type.$this->requestTimeKey;
  }
  
/**
 * Coloca los valores en cada fecha (sea día, mes, hora o día de la semana)
 * Recorre todas las fechas posibles y pone valores a cero ahí donde no hay nada
 * Va a llamar a métodos privados para cada tipo de consulta (día, hora, día de la semana o mes)
 *
 * @param array $result 
 * @return array
 * @since Shokesu 0.1
 */
  public function setDates( $result)
  {
    $type = $this->queryType();
    $method = '__setDates'. ucfirst( $type);
    
    $result = $this->$method( $result);
   
    return $result;
  }
  
/**
 * Coloca los valores en cada fecha
 * Recorre todas las fechas posibles y pone valores a cero ahí donde no hay nada
 *
 * @param array $result 
 * @return array
 * @since Shokesu 0.1
 */
  private function __setDatesDate( $result)
  {
    $dates = $this->getIntervalDate();
    return $this->__setAllDates( $result, $dates);
  }
  
/**
 * Coloca los valores en cada hora
 * Recorre todas las fechas posibles y pone valores a cero ahí donde no hay nada
 *
 * @param array $result 
 * @return array
 * @since Shokesu 0.1
 */
  private function __setDatesHour( $result)
  {
    $dates = array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23);
    return $this->__setAllDates( $result, $dates);
  }
  

/**
 * Coloca los valores en cada día de la semana
 * Recorre todas las fechas posibles y pone valores a cero ahí donde no hay nada
 *
 * @param array $result 
 * @return array
 * @since Shokesu 0.1
 */
  private function __setDatesDayofweek( $result)
  {
    $dates = array( 1, 2, 3, 4, 5, 6, 7);
    $return = $this->__setAllDates( $result, $dates);
    $sunday = current( $return);
    unset( $return [0]);
    $return [] = $sunday;
    return $return;
  }
 
/**
 * Coloca los valores en cada mes
 * Recorre todas las fechas posibles y pone valores a cero ahí donde no hay nada
 *
 * @param array $result 
 * @return array
 * @since Shokesu 0.1
 */
  private function __setDatesMonth( $result)
  {
    $dates = $this->getIntervalMonth();
    return $result;
  }

/**
 * Coloca los valores a cero ahí donde no han nada en $result
 * Los resultados de la consulta a la base de datos nos llegan solo en donde hay valores, 
 *    debido a que GROUP BY solo te devuelve los valores que tienen "algo"
 *    Este método se encarga de rellenar los valores no existentes
 *
 * @param array $result El resultado de la búsqueda 
 * @param array $dates Todas las fechas posibles
 * @return void
 * @since Shokesu 0.1
 */
  private function __setAllDates( $result, $dates)
  {
    $return = array();
    
    foreach( $dates as $key => $date)
    {
      $current = current( $result);
      
      if( $current ['day'] != $date)
      {
        $return [] = array(
            'number' => "0",
            'day' => $date
        );
      }
      else
      {
        $return [] = $current;
        next( $result);
      }
    }
      
    return $return;
  }
  
  
  public function getLegendValues()
  {
    $method = 'getLeyend'. ucfirst( $this->queryType());
    return $this->$method();
  }
  
  public function getLeyendDayofweek()
  {
    $dates = array(  '2013-03-11', '2013-03-12', '2013-03-13', '2013-03-14', '2013-03-15', '2013-03-16', '2013-03-17');
    $return = array();
    
    foreach( $dates as $date)
    {
      $return [] = strftime( "%a", strtotime( $date)); 
    }

    return $return;
  }
  
  public function getLeyendHour()
  {
    return array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23);
  }
  
  
  public function getLeyendMonth()
  {
    return $this->getIntervalMonth();
  }
  
  public function getLeyendDate( $format = 'd-m')
  {
    return $this->getIntervalDate( $format);
  }

  
  public function getIntervalDate( $format = 'Y-m-d')
  {
    $period = new DatePeriod( 
      new DateTime( $this->dateStart()), 
      new DateInterval( 'P1D'), 
      new DateTime( $this->dateEnd()), DatePeriod::EXCLUDE_START_DATE
    );
    
    $dates = array( date( $format, strtotime( $this->dateStart())));
    
    foreach( $period as $dt) 
    {
      $dates [] = $dt->format( $format);
    }
    
    $dates [] = array( date( $format, strtotime( $this->dateEnd())));
    return $dates;
  }
  
  public function getIntervalMonth()
  {
    $time1  = strtotime( $this->dateStart()); 
    $time2  = strtotime( $this->dateEnd()); 
    $my     = date('mY', $time2); 

    $months = array(date('m', $time1)); 

    while($time1 < $time2) 
    { 
      $time1 = strtotime(date('Y-m-d', $time1).' +1 month'); 
      if(date('mY', $time1) != $my && ($time1 < $time2)) 
         $months[] = date('m', $time1); 
    } 

     $months[] = date('m', $time2); 

     return $months;
  }
  
  
/**
 * Devuelve la fecha de inicio solicitada en GET
 *
 * @return date SQL
 * @since Shokesu 0.1
 */
  public function dateStart()
  {
    // $date = isset( $this->Request->query [$this->dateKey( 'startQuery')]) 
    //     ? $this->Request->query [$this->dateKey( 'startQuery')]
    //     : date( 'Y-m-d', strtotime( $this->defaultInterval));
    // 
    
    $date = $this->fromQuery;
    
    if( strtotime( $date) < strtotime( $this->dateMin))
    {
      $date = $this->dateMin;
    }

    return $date;
  }
  
 /**
 * Devuelve la fecha final solicitada en GET
 *
 * @return date SQL
 * @since Shokesu 0.1
 */
  public function dateEnd()
  {
    // $date = isset( $this->Request->query [$this->dateKey( 'endQuery')]) 
    //     ? $this->Request->query [$this->dateKey( 'endQuery')]
    //     : date( 'Y-m-d');
    
    $date = $this->toQuery;
    return $date;
  }
  
/**
 * Devuelve las condiciones tomando los valores de GET para las fechas de inicio y final
 *
 * @param string $pair El campo de la base de datos (tipo Model.column o solo column)
 * @param array $conditions Las condiciones dadas desde la llamada al método
 * @return array Conditions
 * @since Shokesu 0.1
 */
  public function conditions( $pair, $conditions = array())
  {
    $conditions ["DATE($pair) >="] = $this->dateStart();
    $conditions ["DATE($pair) <="] = $this->dateEnd();
    
    return $conditions;
  }
  
/**
 * Devuelve el tipo de petición, atendiendo a lo pedido en la query
 *
 * @return void
 */
  public function queryType()
  {
    if( !empty( $this->queryType))
    {
      return $this->queryType;
    }
    
    if( isset( $this->Request->query [$this->typeQuery]) 
        && array_key_exists( $this->Request->query [$this->typeQuery], $this->queryTypes))
    {
      return $this->Request->query [$this->typeQuery];
    }
    
    return $this->defaultQueryType;
  }
  
  public function queryColumn( $pair)
  {
    $query_type = $this->queryType();
    
    return strtoupper( $query_type) .'('. $pair .')';
  }
  
  public function groupQuery( $pair, $groups = array())
  {
    $type = $this->queryType();
    $info = $this->queryTypes [$type];
    
    $return = array();
    
    foreach( $info as $group)
    {
      $return [] = "$group($pair)";
    }
    
    foreach( $groups as $_group)
    {
      $return [] = $_group;
    }
    
    return $return;
  }
  
  public function setQueryType( $type)
  {
    $this->queryType = $type;
  }
  
  public function fields( $pair, $fields = array())
  {
    $type = $this->queryType();
    $info = $this->queryTypes [$type];
    $return = $fields;
    
    if( count( $info) == 1)
    {
      $return [] = "{$info [0]}($pair) as day";
    }
    else
    {
      $concats = array();
      
      foreach( $info as $group)
      {
        $concats [] = "$group($pair)";
      }
      
      $return [] = 'CONCAT('. implode( ', "-",', $concats) .') as day';
    }
    
    return $return;
  }
  
/**
 * Retorna un array con todos los días de un mes y año dados
 *
 * @param string $date 
 * @return void
 */
  public function daysMonth( $date)
  {
    $days = cal_days_in_month( CAL_GREGORIAN, date( 'm', strtotime( $date)), date( 'Y', strtotime( $date)));
    
    $out = array();
    
    for( $i = 1; $i <= count( $days) ; $i++) 
    { 
      $out [] = $i;
    }
    
    return $out;
  }
  
  public function putZeroValues( $data, $keys)
  {
    
  }
  
  public function total( $data, $field)
  {
    $total = 0;
    
    foreach( $data as $record)
    {
      $record = current( $record);
      $total += $record [$field];
    }
    
    return $total;
  }
  
  public function setPercentage( $results)
  {
    $total = 0;
    
    foreach( $results as $key => $result)
    {
      $total += $result [1];
    }
    
    foreach( $results as $key => $result)
    {
      $percent = !empty( $total) ? round( ($result [1] * 100) / $total) : '0';
      $results [$key][0] .= ' ('. $percent . '%)';
    }
    
    return $results;
  }
  
  
  
}