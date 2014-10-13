Yii MultishipAPI component
==========================

###yii component for use Multiship API (http://docs.multiship.apiary.io/)

1. Copy **"MultishipAPI.php"** to **"protected/components/MultishipAPI.php"**
2. Paste to **"protected/config/main.php"**:
```php
'components'=>array(
...
        'MultishipAPI' => array(
            'class' => 'application.components.MultishipAPI',
        ),
...)
```

###Example "Init Multiship":
```php
$multiship = Yii::app()->MultishipAPI;
$multiship->login = "user_login";
$multiship->password = "user_password";
$multiship->domain = "user_domain";

//@todo save do db config in initAPI();
$status = $multiship->initAPI();
```

###Example "Get city index":
```php
$params = array(
	'city' => Yii::app()->request->getParam('city'),
	'street' => Yii::app()->request->getParam('street'),
	'house' => Yii::app()->request->getParam('house'),
);

$data = $Yii::app()->MultishipAPI->getIndex($params);
```

###Example "Get list of delivery types":
```php
	$city = Yii::app()->request->getParam('city');
	$index = Yii::app()->request->getParam('index');
	$payment_type = Yii::app()->request->getParam('payment_type');
	
	$params = array();
	$params['city_from'] = $this->_multiship->city;
	$params['city_to'] = $city;
	$params['weight'] = 10;
	$params['height'] = 10;
	$params['width'] = 20;
	$params['length'] = 30;
	$params['delivery_type'] = "pickup";
	$params['total_cost'] = 1000;
	$params['index_city'] = (int)$index;
	$params['create_date'] = date("d.m.Y");
	$params['payment_method'] = 1;
	$data = $this->MultishipAPI->searchDeliveryList($params);

```
