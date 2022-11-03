<?php declare(strict_types=1);

namespace Ultra\Store\Switcher;

use Shopware\Core\Framework\Plugin;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin\Context\InstallContext;   
use Shopware\Core\Framework\Plugin\Context\ActivateContext;  
use Shopware\Core\Framework\Plugin\Context\DeactivateContext; 
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;  
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\Uuid\Uuid;

class UltraStoreSwitcher extends Plugin
{
	public function install(InstallContext $context): void
	{

	$connection = $this->container->get(Connection::class);
	   
	   $securing="";
	   $firsturl="";
	   $opt="";
	   
	   $resulter = $connection->fetchAll('
		SELECT 
    sct.name, lo.code, scd.url, sc.access_key
FROM
    sales_channel  sc
INNER JOIN
    sales_channel_translation sct 
	ON sct.sales_channel_id = sc.id
INNER JOIN	
	sales_channel_domain scd
	ON sc.id = scd.sales_channel_id
INNER JOIN
    language la 
	ON la.id = sc.language_id
INNER JOIN
    locale lo 
	ON lo.id = la.locale_id
where sct.name != "Headless"
group by sc.access_key
order by sct.name ASC');
	  	   
	   $myarray="";
	   
	   $topper='<?xml version="1.0" encoding="UTF-8"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/platform/master/src/Core/System/SystemConfig/Schema/config.xsd">

     <!-- InfoBox -->
	<card>
		<component name="sw-highlight-text">
            <name>ultrainfostartbutton0</name>
            <text><![CDATA[<div style="background:#737373; padding: 10px; margin: -30px; text-align: center; font-size: 20px;"><span style="color:#ffffff;">
			<p style="text-align: center;">Thank you for using our Plugin:<br><strong>Sales channel header dropdown menu</strong>.</p>
<p>If you have problems with the configuration, please have a look at the documentation <a title="Ultra-Media.de" href="https://ultra-media.de/Online-Dokumentation/Verkaufskanal-Header-Dropdown-Menue-SW6/" target="_blank" rel="noopener noreferrer"></a> an.</p>
			</span></div>]]></text>
            <text lang="de-DE"><![CDATA[<div style="background:#737373; padding: 10px; margin: -30px; text-align: center; font-size: 20px;"><span style="color:#ffffff;">
			<p style="text-align: center;">Vielen Dank f&uuml;r die Nutzung unseres Plugins:<br><strong>Verkaufskanal Header Dropdown Menü</strong>.</p>
<p>Sollten Sie mit der Konfiguration Probleme haben, so schauen Sie sich bitte die Dokumentation <a title="Ultra-Media.de" href="https://ultra-media.de/Online-Dokumentation/Verkaufskanal-Header-Dropdown-Menue-SW6/" target="_blank" rel="noopener noreferrer">hier</a> an.</p></span></div>]]></text>
        </component>
	</card>


<!-- Verkaufskanal Header Dropdown Menü -->

	<card>
		<component name="sw-highlight-text">
            <name>ultrainfostartbutton</name>
            <text><![CDATA[<div style="background:#364159; padding: 10px; margin: -30px; height: 60px;line-height: 40px; text-align: center; font-size: 20px;"><span style="color:#ffffff;">1. Activate in this saleschannel</span></div>]]></text>
            <text lang="de-DE"><![CDATA[<div style="background:#364159; padding: 10px; margin: -30px; height: 60px;line-height: 40px; text-align: center; font-size: 20px;"><span style="color:#ffffff;">1. Aktivierung in diesem Verkaufskanal</span></div>]]></text>
        </component>
	</card>


	<card>

        <input-field type="bool">
            <name>activeKanal</name>
            <label>Active</label>
            <label lang="de-DE">Aktiv</label>
            <helpText>If selected, plugin will be shown in this shop or subshop.</helpText>
            <helpText lang="de-DE">Wenn gewählt, wird das Plugin in diesem Verkaufskanal angezeigt.</helpText>
        </input-field>
		
	</card>	
	
	';
	   
	   $optionstop = '
	   <!-- Card 2. Verkaufskanal Auswahl -->
	<card>
		<component name="sw-highlight-text">
            <name>ultrainfostartbutton</name>
            <text><![CDATA[<div style="background:#364159; padding: 10px; margin: -30px; height: 60px;line-height: 40px; text-align: center; font-size: 20px;"><span style="color:#ffffff;">
			2. Turn on flag(s)
			</span></div>]]></text>
            <text lang="de-DE"><![CDATA[<div style="background:#364159; padding: 10px; margin: -30px; height: 60px;line-height: 40px; text-align: center; font-size: 20px;"><span style="color:#ffffff;">
			2. Flagge(n) einschalten
			</span></div>]]></text>
        </component>
	</card>
	   
    <card>
		<input-field type="bool">
            <name>flagonoff</name>
			<label>Turn on flag(s)</label>
			<label lang="de-DE">Flagge(n) einschalten</label>
        </input-field>
	</card>
';
	   
	   $optionsbottom = '

</config>';
	   
	   $i=0;
	   $file = fopen("../custom/plugins/UltraStoreSwitcher/src/Resources/config/config.xml", "w");
	   
	   foreach ($resulter as $value) {
		$i=$i+1;
		$opt = "";
		
		$urli = $connection->fetchAll('
		SELECT 
    sct1.name, lo1.code, scd1.url, sc1.access_key
FROM
    sales_channel  sc1
INNER JOIN
    sales_channel_translation sct1 
	ON sct1.sales_channel_id = sc1.id
INNER JOIN	
	sales_channel_domain scd1
	ON sc1.id = scd1.sales_channel_id
INNER JOIN
    language la1 
	ON la1.id = sc1.language_id
INNER JOIN
    locale lo1 
	ON lo1.id = la1.locale_id
where sct1.name != "Headless" and sc1.access_key = "' .$value["access_key"] .'"
order by scd1.url asc
		');
		$firsturl="";
		
		foreach ($urli as $value1) {
			if ($firsturl == "") {
				$firsturl = $value1["url"];
			}
			$opt = $opt .'
			<option>
                    <id>' .$value1["url"] .'</id>
                    <name>' .$value1["url"] .'</name>
                    <name lang="de-DE">' .$value1["url"] .'</name>
                </option>
			';
		}
		
		
		
		
		$myarray= $myarray .'
		
		<card>
		<component name="sw-highlight-text">
            <name>ultrainfostartbutton' .($i+2) .'</name>
            <text><![CDATA[<div style="background:#364159; padding: 10px; margin: -30px; height: 60px;line-height: 40px; text-align: center; font-size: 20px;"><span style="color:#ffffff;">
			' .($i+2) ." " .$value["name"] .'
			</span></div>]]></text>
            <text lang="de-DE"><![CDATA[<div style="background:#364159; padding: 10px; margin: -30px; height: 60px;line-height: 40px; text-align: center; font-size: 20px;"><span style="color:#ffffff;">
			' .($i+2) ." " .$value["name"] .'
			</span></div>]]></text>
        </component>
		</card>
		
		<card>
		
		<input-field type="bool">
            <name>Kanal' .$i .'langonoff</name>
			<label>Activate</label>
			<label lang="de-DE">Einschalten</label>
        </input-field>
		
		<input-field type="text">
            <name>Kanal' .$i .'name</name>
			<label>Own Label</label>
			<label lang="de-DE">Eigene Bezeichnung</label>
        </input-field>
		
		<input-field type="single-select">
            <name>Kanal' .$i .'kanal</name>
            <label>' .$value["name"] .'</label>
            <label lang="de-DE">' .$value["name"] .'</label>
			<options>
				 <option>
                    <id>' .$value["name"] .";;" .$value["code"] .';;' .$value["access_key"] .'</id>
                    <name>' .$value["name"] .'</name>
                    <name lang="de-DE">' .$value["name"] .'</name>
                </option>
			</options>
			<disabled>true</disabled>
        </input-field>
		
		
		<input-field type="single-select">
            <name>Kanal' .$i .'url</name>
            <label>Choose URL</label>
            <label lang="de-DE">URL auswählen</label>
			<options>
				 ' .$opt .'
			</options>
        </input-field>
		
		<input-field type="text">
            <name>Kanal' .$i .'title</name>
			<label>Own Title</label>
			<label lang="de-DE">Eigener Title</label>
        </input-field>
		
		<input-field type="bool">
            <name>Kanal' .$i .'ownflagonoff</name>
			<label>Turn on own picture</label>
			<label lang="de-DE">Eigenes Bild einschalten</label>
        </input-field>
		
		<component name="sw-media-field">
			<name>Kanal' .$i .'flagger</name>
			<label>Own picture</label>
			<label lang="de-DE">Eigenes Bild</label>
		</component>

	</card>';

		$this->setValue('Kanal' .$i ."kanal", $value["name"] .";;" .$value["code"] .';;' .$value["access_key"]);
		$this->setValue('Kanal' .$i .'langonoff', 1);
		$this->setValue('flagonoff', 1);
		$this->setValue('Kanal' .$i .'url', $firsturl);
		$this->setValue('activeKanal', true);
		
		}
		fwrite($file,$topper .$optionstop .$myarray .$optionsbottom);
		fclose($file);
		
		$customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetRepository->upsert([$this->getFieldSet()], $context->getContext());
		
		$sc_name = $connection->fetchAll('SELECT * FROM sales_channel_translation') ;

		foreach ($sc_name as $value2) {
			if($value2["custom_fields"] == '{"custom_verkaufskanal_name": null}' or $value2["custom_fields"] == 'NULL' or $value2["custom_fields"] == '') {
				$ergebnis='{"custom_verkaufskanal_name": "' .$value2["name"] .'"}';
				$connection->executeUpdate('UPDATE sales_channel_translation SET custom_fields =' .chr(39) .$ergebnis .chr(39) .' WHERE name="' .$value2["name"] .'"');
			}
			
		}
		$connection->executeUpdate("UPDATE mail_template_translation SET sender_name = REPLACE(sender_name,'{{ salesChannel.name }}','{{salesChannel.customFields.custom_verkaufskanal_name}}')");
		$connection->executeUpdate("UPDATE mail_template_translation SET subject = REPLACE(subject,'{{ salesChannel.translated.name }}','{{salesChannel.customFields.custom_verkaufskanal_name}}')");
		
		
	}
	public function activate(ActivateContext $context): void
   {
	   
		
   }
	public function deactivate(DeactivateContext $context): void
	{
		
	}
	
	public function update(UpdateContext $context): void
	{
		if (file_exists('../public/bundles/administration/static/css/ultra-config-styler.css')) {		
		unlink('../public/bundles/administration/static/css/ultra-config-styler.css');
		}	
		
		if (file_exists('../public/bundles/administration/static/js/ultra-config-styler.js')) {		
		unlink('../public/bundles/administration/static/js/ultra-config-styler.js');
		}
	}
	
	public function uninstall(UninstallContext $context): void
	{
		parent::uninstall($context);
		$connection = $this->container->get(Connection::class);
		$connection->executeUpdate('DELETE FROM custom_field_set where name = "ultra_saleschannel"');
		
		if (file_exists('../public/bundles/administration/static/css/ultra-config-styler.css')) {		
		unlink('../public/bundles/administration/static/css/ultra-config-styler.css');
		}	
		
		if (file_exists('../public/bundles/administration/static/js/ultra-config-styler.js')) {		
		unlink('../public/bundles/administration/static/js/ultra-config-styler.js');
		}

		if ($context->keepUserData()) {
			return;
		}
		$connection->executeUpdate('delete FROM `system_config` where configuration_key like "UltraStoreSwitcher%"');
		$connection->executeUpdate("UPDATE mail_template_translation SET sender_name = REPLACE(sender_name,'{{salesChannel.customFields.custom_verkaufskanal_name}}','{{ salesChannel.name }}')");
		$connection->executeUpdate("UPDATE mail_template_translation SET subject = REPLACE(subject,'{{salesChannel.customFields.custom_verkaufskanal_name}}','{{ salesChannel.translated.name }}')");
		
	}

	
	public function setValue(string $configName, $default = null) : void
    {
        $systemConfigService = $this->container->get(SystemConfigService::class);
        $domain = $this->getName() . '.config.';

        if( $systemConfigService->get($domain . $configName) === null )
        {
            $systemConfigService->set($domain . $configName, $default);
        }
    }
	
	private function getFieldSet()
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => 'ultra_saleschannel',
            'config' => [
				"translated" => true,
                'label' => [
                    'de-DE' => 'Verkaufskanal Header Dropdown Menü',
                    'en-GB' => 'Sales channel header dropdown menu'
                ],
            ],
            'customFields' => [

                [
                    'id' => Uuid::randomHex(),
                    'name' => 'custom_verkaufskanal_name',
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
						'componentName' => 'sw-field',
						'customFieldType' => 'text',
                        'label' => [
                            'de-DE' => 'Verkaufskanal Name',
                            'en-GB' => 'Saleschannel name',
                        ],
                        'customFieldPosition' => 1
                    ],
                ],
            ],
            'relations' => [
                ['id' => Uuid::randomHex(), 'entityName' => 'sales_channel'],
            ]
        ];
    }
	
	

}
