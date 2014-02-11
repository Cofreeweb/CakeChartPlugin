<?php

class StatsHelper extends AppHelper 
{
  public $helpers = array('Html', 'Form', 'Session', 'Js');
  
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
  
/**
 * Los tipos de peticiones posibles
 *
 * @var array
 */
  private $queryTypes = array(
      'hour' => 'Cada hora',
      'date' => 'Día',
      'month' => 'Mes'
  );
  
  
/**
* El key del query que determina el tipo de petición
 *
 * @var string
 */
  private $typeQuery = 'type';
  

/**
 * El tipo de petición por defecto
 *
 * @var string
 */
  private $defaultQueryType = 'date';
  
  
/**
 * El intervalo de fecha mostrado por defecto, que será desde la fecha de hoy restado a lo indicado en esta variable
 *
 * @var string
 */
  private $defaultInterval = '-1 month';
  
  
/**
 * El elemento del datepicker
 *
 * @var string
 */
  private $datepickerElement = '#datepicker';
  

  public function beforeRender($viewFile) 
  {
    $this->setDateQueries();
    parent::beforeRender( $viewFile);
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
    $this->fromQuery = "$from";
    
    list( $day, $month, $year) = explode( '/', $to);
    $this->toQuery = "$to"; 
    
  }
  
  
 public function dateKey( $type, $key = 1)
 {
   return $this->$type.$key;
 }
  
/**
 * Devuelve el formulario de opciones para mostrar los gráficos
 *
 * @return string HTML
 * @since Shokesu 0.1
 */
  public function form( $options = array())
  {
    $_options = array(
        'dateFilter' => true
    );
    
    $options = array_merge( $_options, $options);
    
    $out = array();
    
    if( !$options ['dateFilter'])
    {
      return;
    }
 
    $out [] = $this->Form->create( null, array(
        'type' => 'get',
        'id' => 'form-stats'
    ));
    
    
    $out [] = '<input type="text" name="dates" id="stats-dates" value="'. $this->fromQuery .' - '. $this->toQuery .'" />';
    
    $out [] = $this->Form->end();
    
    $texts = array(
        'aplicar' => __d( 'stats', "Aplicar"),
        'cancelar' => __d( 'stats', "Cancelar"),
        'desde' => __d( 'stats', "Desde"),
        'hasta' => __d( 'stats', "Hasta")
    );
    
      $js = <<<EOF
    $(function(){
      $('#stats-dates').daterangepicker({
        startDate: "{$this->fromQuery}",
        endDate: "{$this->toQuery}",
        format: 'DD/MM/YYYY',
        locale: {
            applyLabel: '{$texts ['aplicar']}',
            cancelLabel: '{$texts ['cancelar']}',
            fromLabel: '{$texts ['desde']}',
            toLabel: '{$texts ['hasta']}',
            weekLabel: 'W',
            customRangeLabel: 'Custom Range',
            daysOfWeek: moment()._lang._weekdaysMin.slice(),
            monthNames: moment()._lang._monthsShort.slice(),
            firstDay: 0
        },
        applyClass: 'btn btn-success btn-sm',
        cancelClass: 'btn btn-default btn-sm'
      });
      
      $('#stats-dates').on('apply', function(ev, picker) {
        $("#form-stats").submit();
      });
    })
EOF;

      $this->Js->buffer( trim( $js));
    
    return implode( "\n", $out);
  }
  
  
/**
 * Devuelve la fecha desde cuando se muestran las estadísticas
 *
 * @return string date
 * @since Shokesu 0.1
 */
  public function dateFrom( $key)
  {
    // Si la fecha está marcada desde la petición GET
    if( isset( $this->request->query [$key]))
    {
      $date = $this->request->query [$key];
    } 
    // Si no se muestra la fecha de hoy menos lo indicado en defaultInterval
    else
    {
      $date = date( 'Y-m-d', strtotime( $this->defaultInterval));
    }
    
    if( strtotime( $date) < strtotime( $this->dateMin))
    {
      $date = $this->dateMin;
    }
    
    return $date;
  }  
  
/**
 * Devuelve la fecha hasta cuando se muestran las estadísticas
 *
 * @return string date
 * @since Shokesu 0.1
 */
  public function dateTo( $key)
  {
    // Si la fecha está marcada desde la petición GET
    if( isset( $this->request->query [$key]))
    {
      $date = $this->request->query [$key];
    } 
    // Si no se muestra la fecha de hoy
    else
    {
      $date = date( 'Y-m-d', strtotime( '-1 day'));
    }
    
    return $date;
  }
  
  
 /**
 * Devuelve la fecha de inicio solicitada en GET
 *
 * @return date SQL
 * @since Shokesu 0.1
 */
  public function getDateStart( $key)
  {
    $date = isset( $this->request->query [$this->dateKey( 'startQuery', $key)]) 
        ? $this->request->query [$this->dateKey( 'startQuery', $key)]
        : date( 'Y-m-d', strtotime( $this->defaultInterval));

    return $date;
  }

 /**
 * Devuelve la fecha final solicitada en GET
 *
 * @return date SQL
 * @since Shokesu 0.1
 */
  public function getDateEnd( $key)
  {
    $date = isset( $this->request->query [$this->dateKey( 'endQuery', $key)]) 
        ? $this->request->query [$this->dateKey( 'endQuery', $key)]
        : date( 'Y-m-d');

    return $date;
  }
  
/**
 * Devuelve el tipo de petición
 *
 * @return string
 * @since Shokesu 0.1
 */
  public function type()
  {
    if( isset( $this->request->query [$this->typeQuery]))
    {
      return $this->request->query [$this->typeQuery];
    }
    
    return $this->defaultQueryType;
  }
  

  public function queryTypes()
  {
    return false;
    return $this->Form->input( 'type', array(
        'type' => 'radio',
        'options' => $this->queryTypes,
        'value' => $this->type(),
        'legend' => "Tipo",
        'hiddenField' => false
    ));
  }
  
  public function minDay()
  {
    if( !isset( $this->request->params ['site_id']))
    {
      return '2013-03-10';
    }
    
    $site_created = Configure::read( 'SiteManager.Site.created');
    
    if( isset( $this->request->params ['admin']))
    {
      return $site_created;
    }
    
    $user = $this->_getModel( 'Site')->usersByRole( $this->request->params ['site_id'], array( 'Webmaster'));
    $service = $this->_getModel( 'Site')->serviceManager( $this->request->params ['site_id']);
    $item = $this->_getModel( 'LineItem')->currentItemUnique( $service ['ServiceType']['service_group_id'], $user [0]['User']['id']);
    
    if( strtotime( $site_created) > strtotime( $item ['LineItem']['created']))
    {
      return $site_created;
    }
    
    return $item ['LineItem']['created'];
  }
  
/**
 * Devuelve el javascript para mostrar el calendario con el plugin de jQuery datepicker
 *
 * @return void
 * @since Shokesu 0.1
 */
  public function datepickerScript( $key = 1)
  {
    $date_from = $this->dateFrom( $this->dateKey( 'startQuery', $key));
    $date_to = $this->dateTo( $this->dateKey( 'endQuery', $key));
    $today = date( "Y-m-d");
    $max_day = date( "Y-m-d", strtotime( "-1 day"));
    $min_day = date( "Y-m-d", strtotime( $this->minDay() . ' +2 day'));
    $js = <<<EOF
  var key = $key;
  $('{$this->datepickerElement}').DatePicker({
    flat: true,
    date: ['{$date_from}','{$date_to}'],
    current: '$today',
    calendars: 2,
    mode: 'range',
    id: 'datepicker_$key',
    starts: 1,
    minDay: '$min_day',
    maxDay: '$max_day',
    onChange: function(a, b){
      var dates = a.toString().split( ',');
      $("#{$this->dateKey( 'startQuery', $key)}").val( dates[0]).change();
      $("#{$this->dateKey( 'endQuery', $key)}").val( dates[1]).change();
      $(this).trigger( 'change');
      $("#form-input-period").val( "custom");
    },

    locale: {
			days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
			daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
			daysMin: ["Dom", "Lun", "Mar", "Mie", "Jue", "Vie", "Sab", "Dom"],
			months: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
			monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
			weekMin: 'wk'
		}
  });
  $(".datepickerWrap").hide();
EOF;
  
    if( !isset( $this->request->query ['compare']) || $this->request->query ['compare'] != 1)
    {
      $js .= '
        $("#datepicker_2").data( "hide", true);
        $("#form-date2-cnt").hide();
        $("#form-input-period").hide();
      ';
    }
    $out = trim( "$(function(){ $js })");

    return $this->Js->buffer( $out);
    
  }
}