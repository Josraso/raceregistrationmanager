<?php
// raceregistrationmanager.php - Archivo principal del módulo
if (!defined('_PS_VERSION_')) {
    exit;
}

class RaceRegistrationManager extends Module
{
    public function __construct()
    {
        $this->name = 'raceregistrationmanager';
        $this->tab = 'administration';
        $this->version = '4.0.5';
        $this->author = 'Race Manager Pro';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '8.99'];

        parent::__construct();

        $this->displayName = $this->l('Race Registration Manager');
        $this->description = $this->l('Gestión avanzada de inscripciones para múltiples carreras de motos');
    }

    public function install()
{
    // Verificar que el módulo Product Fields Manager esté instalado
    if (!Module::isInstalled('an_productfields')) {
        $this->_errors[] = $this->l('Este módulo requiere que esté instalado "Product Fields Manager" (an_productfields). Por favor, instálalo primero.');
        return false;
    }
    
    // Verificar que el módulo esté activo
    if (!Module::isEnabled('an_productfields')) {
        $this->_errors[] = $this->l('El módulo "Product Fields Manager" (an_productfields) debe estar activado para instalar este módulo.');
        return false;
    }

    return parent::install() && 
        $this->installDB() && 
        $this->registerHook('actionValidateOrder') &&
        $this->registerHook('displayBackOfficeHeader');
}


    public function uninstall()
    {
        return parent::uninstall() && 
            $this->uninstallDB();
    }

    private function installDB()
    {
        $sql = [];
        
        // Tabla principal de inscripciones con soporte para múltiples carreras
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'race_registrations` (
            `id_registration` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_order` INT UNSIGNED NOT NULL,
            `id_cart` INT UNSIGNED NOT NULL,
            `id_product` INT UNSIGNED NULL,
            `field_data` TEXT NOT NULL,
            `validated` TINYINT(1) NOT NULL DEFAULT 0,
            `published` TINYINT(1) NOT NULL DEFAULT 0,
            `race_status` VARCHAR(20) DEFAULT "active",
            `race_date` DATE NULL,
            `archive_date` DATETIME NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NULL,
            PRIMARY KEY (`id_registration`),
            INDEX `id_order` (`id_order`),
            INDEX `id_cart` (`id_cart`),
            INDEX `idx_product_status` (`id_product`, `race_status`),
            INDEX `idx_race_date` (`race_date`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        // Tabla de configuración por carrera
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'race_product_config` (
    `id_config` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT UNSIGNED NOT NULL,
    `display_fields` TEXT NULL,
    `category_field` VARCHAR(255) NULL,
    `list_field` VARCHAR(255) NULL,
    `settings` TEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NULL,
    PRIMARY KEY (`id_config`),
    UNIQUE KEY `id_product` (`id_product`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
        
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        
        return true;
    }

    private function uninstallDB()
    {
        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'race_registrations`';
        $sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'race_product_config`';
        
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
        }
    }

    public function hookActionValidateOrder($params)
    {
        if (!isset($params['order']) || !Validate::isLoadedObject($params['order'])) {
            return false;
        }

        $order = $params['order'];
        
        // Obtener todos los productos de carrera del pedido
        $raceProducts = $this->getAllRaceProductsFromOrder($order->id);
        
        if (!empty($raceProducts)) {
            foreach ($raceProducts as $raceProduct) {
                // DEBUG: Log para verificar que se detectan productos de carrera
                if (_PS_MODE_DEV_) {
                    PrestaShopLogger::addLog('Race Registration Debug - Producto detectado: ' . $raceProduct['product_id'] . ' en pedido: ' . $order->id, 1);
                }
                
                $customFields = $this->getOrderCustomFields($order->id_cart, $raceProduct['product_id']);
                
                // Si no hay campos específicos para este producto, buscar campos generales
                if (empty($customFields)) {
                    $customFields = $this->getOrderCustomFields($order->id_cart);
                }
                
                $this->saveRegistrationData($order->id, $order->id_cart, $customFields, $raceProduct['product_id']);
            }
            $this->context->controller->confirmations[] = $this->l('Datos de inscripción guardados correctamente');
        }
    }

    private function getAllRaceProductsFromOrder($id_order)
    {
        // DEBUG: Log productos encontrados
        if (_PS_MODE_DEV_) {
            $products = Db::getInstance()->executeS('
                SELECT DISTINCT od.product_id, od.product_quantity, pl.name
                FROM `'._DB_PREFIX_.'order_detail` od
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
                    od.product_id = pl.id_product 
                    AND pl.id_lang = '.(int)$this->context->language->id.'
                )
                WHERE od.id_order = '.(int)$id_order.'
                AND (pl.name LIKE "%carrera%" OR pl.name LIKE "%race%" OR od.product_reference LIKE "RACE%")
            ');
            PrestaShopLogger::addLog('Race Registration Debug - Productos de carrera encontrados en pedido ' . $id_order . ': ' . count($products), 1);
            foreach ($products as $prod) {
                PrestaShopLogger::addLog('Race Registration Debug - Producto: ' . $prod['product_id'] . ' - ' . $prod['name'], 1);
            }
        }
        
        // Buscar todos los productos que sean carreras en el pedido
        return Db::getInstance()->executeS('
            SELECT DISTINCT od.product_id, od.product_quantity, pl.name
            FROM `'._DB_PREFIX_.'order_detail` od
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
                od.product_id = pl.id_product 
                AND pl.id_lang = '.(int)$this->context->language->id.'
            )
            WHERE od.id_order = '.(int)$id_order.'
            AND (pl.name LIKE "%carrera%" OR pl.name LIKE "%race%" OR od.product_reference LIKE "RACE%")
        ');
    }

    private function getRaceProductFromOrder($id_order)
    {
        // Buscar productos que sean carreras en el pedido
        $products = Db::getInstance()->executeS('
            SELECT DISTINCT od.product_id, pl.name
            FROM `'._DB_PREFIX_.'order_detail` od
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
                od.product_id = pl.id_product 
                AND pl.id_lang = '.(int)$this->context->language->id.'
            )
            WHERE od.id_order = '.(int)$id_order.'
            AND (pl.name LIKE "%carrera%" OR pl.name LIKE "%race%" OR od.product_reference LIKE "RACE%")
        ');
        
        if (!empty($products)) {
            return (int)$products[0]['product_id'];
        }
        
        // Si no se encuentra, buscar cualquier producto del pedido
        return (int)Db::getInstance()->getValue('
            SELECT product_id 
            FROM `'._DB_PREFIX_.'order_detail` 
            WHERE id_order = '.(int)$id_order.' 
            LIMIT 1
        ');
    }

    private function getOrderCustomFields($id_cart, $id_product = null)
    {
        if (empty($id_cart)) {
            return [];
        }

        $where = 'WHERE pc.id_cart = '.(int)$id_cart;
        if ($id_product) {
            $where .= ' AND pc.id_product = '.(int)$id_product;
        }

        $hasFields = Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `'._DB_PREFIX_.'an_productfields_cart` pc
            '.$where
        );

        if (!$hasFields) {
            return [];
        }

        return Db::getInstance()->executeS('
            SELECT 
                pc.field_name,
                pc.value,
                pc.id_product,
                pfl.name as field_label
            FROM `'._DB_PREFIX_.'an_productfields_cart` pc
            LEFT JOIN `'._DB_PREFIX_.'an_productfields` pf ON (pc.id_an_productfields = pf.id_an_productfields)
            LEFT JOIN `'._DB_PREFIX_.'an_productfields_lang` pfl ON (
                pf.id_an_productfields = pfl.id_an_productfields 
                AND pfl.id_lang = '.(int)$this->context->language->id.'
            )
            '.$where.'
            ORDER BY pc.id_product
        ');
    }

    private function saveRegistrationData($id_order, $id_cart, $fields, $id_product = null)
    {
        $fieldData = [];
        foreach ($fields as $field) {
            if (!empty($field['field_name'])) {
                $fieldName = !empty($field['field_label']) ? $field['field_label'] : $field['field_name'];
                $fieldData[$fieldName] = $field['value'];
            }
        }

        if (!empty($fieldData) || $id_product) {
            // DEBUG: Log para verificar que se guarda la inscripción
            if (_PS_MODE_DEV_) {
                PrestaShopLogger::addLog('Race Registration Debug - Guardando inscripción para producto: ' . $id_product . ' pedido: ' . $id_order, 1);
            }
            
            return Db::getInstance()->insert('race_registrations', [
                'id_order' => (int)$id_order,
                'id_cart' => (int)$id_cart,
                'id_product' => (int)$id_product,
                'field_data' => pSQL(json_encode($fieldData)),
                'race_status' => 'active',
                'date_add' => date('Y-m-d H:i:s')
            ], false, true, Db::REPLACE);
        }
        return false;
    }

    public function getContent()
    {
        $output = '';
        
        // PROCESAR CAMBIO DE CARRERA CON BOTÓN
        if (Tools::isSubmit('changeRaceBtn')) {
            $newSelectedProduct = (int)Tools::getValue('race_selector');
            if ($newSelectedProduct > 0) {
                $this->context->cookie->selected_race_product = $newSelectedProduct;
                $this->context->cookie->write();
                
                // DEBUG
                if (_PS_MODE_DEV_) {
                    $output .= '<div class="alert alert-success">
                        <p>CAMBIO DE CARRERA EXITOSO:</p>
                        <p>Nuevo producto seleccionado: ' . $newSelectedProduct . '</p>
                        <p>Cookie actualizada correctamente</p>
                    </div>';
                }
                
                // Redirigir para limpiar POST
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&selected_product='.$newSelectedProduct);
                exit;
            } else {
                $output .= $this->displayError($this->l('Debes seleccionar una carrera válida'));
            }
        }

        // Obtener el producto seleccionado
        $selectedProduct = (int)Tools::getValue('selected_product', 0);

        // Si no hay producto en la URL, buscar en la cookie
        if (!$selectedProduct && isset($this->context->cookie->selected_race_product)) {
            $selectedProduct = (int)$this->context->cookie->selected_race_product;
        }

        // Si no hay producto seleccionado, buscar el primero disponible
        if (!$selectedProduct) {
            $raceProducts = $this->getRaceProducts();
            if (!empty($raceProducts)) {
                $selectedProduct = (int)$raceProducts[0]['id_product'];
                // Guardar en cookie el primer producto
                $this->context->cookie->selected_race_product = $selectedProduct;
                $this->context->cookie->write();
            }
        }

        // DEBUG: Mostrar valores recibidos
        if (_PS_MODE_DEV_) {
            $output .= '<div class="alert alert-warning">
                <p>DEBUG INFO:</p>
                <p>POST race_selector: ' . Tools::getValue('race_selector') . '</p>
                <p>GET selected_product: ' . Tools::getValue('selected_product') . '</p>
                <p>Cookie selected_race_product: ' . (isset($this->context->cookie->selected_race_product) ? $this->context->cookie->selected_race_product : 'NOT SET') . '</p>
                <p>Final selectedProduct: ' . $selectedProduct . '</p>
                <p>changeRaceBtn submitted: ' . (Tools::isSubmit('changeRaceBtn') ? 'YES' : 'NO') . '</p>
            </div>';
        }
        
        // Acciones de descarga
        if (Tools::isSubmit('action')) {
            $action = Tools::getValue('action');
            
            if ($action === 'downloadPrestashopHtml') {
                $this->downloadPrestashopHtml($selectedProduct);
            } elseif ($action === 'downloadCsv') {
                $this->downloadCsv($selectedProduct);
            }
        }
        
        // Acciones AJAX
        if (Tools::isSubmit('ajax')) {
            $action = Tools::getValue('action');
            
            if ($action == 'process_action') {
                $this->processAjaxActions();
                exit;
            } elseif ($action == 'get_race_config') {
                $this->getRaceConfigAjax();
                exit;
            }
        }
        
        // Procesar formularios
        $output .= $this->processConfiguration($selectedProduct);
        $output .= $this->processPublications();
        $output .= $this->processValidateSelectedOrders();
        $output .= $this->unvalidateSelectedOrders();
        $output .= $this->unpublishSelectedOrders();
        $output .= $this->processArchiveRace();
        $output .= $this->processUnarchiveRace();
        
        // Asignar variables para la plantilla
        $templateVars = $this->getTemplateVariables($selectedProduct);
        $templateVars['module'] = $this;
        $this->context->smarty->assign($templateVars);
        
        // Generar HTML de exportación
        $exportableHtml = $this->getPrestashopHtml($selectedProduct);
        $this->context->smarty->assign('exportable_prestashop_html', $exportableHtml);
        
        // Cargar plantillas
        return $output . 
            $this->display(__FILE__, 'views/templates/admin/race_selector.tpl') .
            $this->display(__FILE__, 'views/templates/admin/configure.tpl') . 
            $this->display(__FILE__, 'views/templates/admin/export.tpl');
    }
    
    private function getRaceProducts()
    {
        return Db::getInstance()->executeS('
            SELECT DISTINCT p.id_product, pl.name, p.reference, p.active,
                (SELECT COUNT(*) FROM `'._DB_PREFIX_.'race_registrations` r 
                 WHERE r.id_product = p.id_product) as registrations_count,
                (SELECT r.race_status FROM `'._DB_PREFIX_.'race_registrations` r 
                 WHERE r.id_product = p.id_product LIMIT 1) as race_status
            FROM `'._DB_PREFIX_.'product` p
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
                p.id_product = pl.id_product 
                AND pl.id_lang = '.(int)$this->context->language->id.'
            )
            WHERE p.active = 1
            AND (pl.name LIKE "%carrera%" OR pl.name LIKE "%race%" OR p.reference LIKE "RACE%")
            ORDER BY p.id_product DESC
        ');
    }
    
    private function getRaceConfigAjax()
    {
        $id_product = (int)Tools::getValue('id_product');
        $config = $this->getRaceConfiguration($id_product);
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'config' => $config]);
        exit;
    }
    
    private function getRaceConfiguration($id_product)
    {
        $config = Db::getInstance()->getRow('
            SELECT * FROM `'._DB_PREFIX_.'race_product_config`
            WHERE id_product = '.(int)$id_product
        );
        
        if ($config) {
            $config['display_fields'] = json_decode($config['display_fields'], true) ?: [];
            $config['settings'] = json_decode($config['settings'], true) ?: [];
        } else {
           
    // Configuración por defecto
    $config = [
        'id_product' => $id_product,
        'display_fields' => [],
        'category_field' => 'categoria',
        'list_field' => '',
        'settings' => []
    ];
}
        
        return $config;
    }
    
    private function saveRaceConfiguration($id_product, $config)
    {
        $data = [
    'id_product' => (int)$id_product,
    'display_fields' => pSQL(json_encode($config['display_fields'])),
    'category_field' => pSQL($config['category_field']),
    'list_field' => pSQL($config['list_field']),
    'settings' => pSQL(json_encode($config['settings'])),
    'date_upd' => date('Y-m-d H:i:s')
];
        
        $exists = Db::getInstance()->getValue('
            SELECT id_config FROM `'._DB_PREFIX_.'race_product_config`
            WHERE id_product = '.(int)$id_product
        );
        
        if ($exists) {
            return Db::getInstance()->update('race_product_config', $data, 'id_product = '.(int)$id_product);
        } else {
            $data['date_add'] = date('Y-m-d H:i:s');
            return Db::getInstance()->insert('race_product_config', $data);
        }
    }
    
    private function processArchiveRace()
    {
        if (!Tools::isSubmit('archiveRace')) {
            return '';
        }
        
        $id_product = (int)Tools::getValue('archive_product');
        
        if (!$id_product) {
            return $this->displayError($this->l('Debes seleccionar una carrera para archivar'));
        }
        
        $result = Db::getInstance()->update('race_registrations',
            ['race_status' => 'archived', 'archive_date' => date('Y-m-d H:i:s')],
            'id_product = '.(int)$id_product.' AND race_status = "active"'
        );
        
        return $result ? 
            $this->displayConfirmation($this->l('Carrera archivada correctamente')) :
            $this->displayError($this->l('Error al archivar la carrera'));
    }
    
    private function processUnarchiveRace()
    {
        if (!Tools::isSubmit('unarchiveRace')) {
            return '';
        }
        
        $id_product = (int)Tools::getValue('unarchive_product');
        
        if (!$id_product) {
            return $this->displayError($this->l('Debes seleccionar una carrera para desarchivar'));
        }
        
        $result = Db::getInstance()->update('race_registrations',
            ['race_status' => 'active', 'archive_date' => null],
            'id_product = '.(int)$id_product.' AND race_status = "archived"'
        );
        
        if ($result) {
            // Redirigir a la carrera desarchivada
            $this->context->cookie->selected_race_product = $id_product;
            $this->context->cookie->write();
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name);
        } else {
            return $this->displayError($this->l('Error al desarchivar la carrera'));
        }
    }
    
    private function processAjaxActions()
    {
        $action = Tools::getValue('actionType');
        $response = ['status' => 'error', 'message' => 'Acción no válida'];
        
        switch ($action) {
            case 'process_validate':
                $orderId = (int)Tools::getValue('id');
                $response = $this->processAndValidateSingleOrder($orderId) ?
                    ['status' => 'success', 'message' => 'Pedido procesado y validado correctamente'] :
                    ['status' => 'error', 'message' => 'Error al procesar y validar el pedido'];
                break;
                
            case 'unvalidate':
                $orderId = (int)Tools::getValue('id');
                $response = $this->unvalidateSingleOrder($orderId) ?
                    ['status' => 'success', 'message' => 'Validación anulada correctamente'] :
                    ['status' => 'error', 'message' => 'Error al anular la validación'];
                break;
                
            case 'publish':
                $regId = (int)Tools::getValue('id');
                $response = $this->publishSingleRegistration($regId) ?
                    ['status' => 'success', 'message' => 'Inscripción publicada correctamente'] :
                    ['status' => 'error', 'message' => 'Error al publicar la inscripción'];
                break;
                
            case 'unpublish':
                $regId = (int)Tools::getValue('id');
                $response = $this->unpublishSingleRegistration($regId) ?
                    ['status' => 'success', 'message' => 'Publicación anulada correctamente'] :
                    ['status' => 'error', 'message' => 'Error al anular la publicación'];
                break;
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    private function processAndValidateSingleOrder($orderId)
    {
        $order = new Order((int)$orderId);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }
        
        $cartId = $order->id_cart;
        $raceProducts = $this->getAllRaceProductsFromOrder($orderId);
        
        if (empty($raceProducts)) {
            // Si no hay productos de carrera, buscar cualquier producto
            $id_product = $this->getRaceProductFromOrder($orderId);
            if ($id_product) {
                $raceProducts = [['product_id' => $id_product]];
            }
        }
        
        $success = false;
        foreach ($raceProducts as $raceProduct) {
            $exists = Db::getInstance()->getValue('
                SELECT id_registration 
                FROM `'._DB_PREFIX_.'race_registrations` 
                WHERE id_order = '.(int)$orderId.'
                AND id_product = '.(int)$raceProduct['product_id']
            );
            
            if (!$exists) {
                $customFields = $this->getOrderCustomFields($cartId, $raceProduct['product_id']);
                
                // Si no hay campos específicos, buscar generales
                if (empty($customFields)) {
                    $customFields = $this->getOrderCustomFields($cartId);
                }
                
                if ($this->saveRegistrationData($orderId, $cartId, $customFields, $raceProduct['product_id'])) {
                    $registrationId = Db::getInstance()->Insert_ID();
                } else {
                    continue;
                }
            } else {
                $registrationId = $exists;
            }
            
            if (Db::getInstance()->update('race_registrations', 
                ['validated' => 1, 'date_upd' => date('Y-m-d H:i:s')], 
                'id_registration = '.(int)$registrationId
            )) {
                $success = true;
            }
        }
        
        return $success;
    }
    
    private function unvalidateSingleOrder($orderId)
    {
        $registrations = Db::getInstance()->executeS('
            SELECT id_registration 
            FROM `'._DB_PREFIX_.'race_registrations` 
            WHERE id_order = '.(int)$orderId
        );
        
        if (empty($registrations)) {
            return false;
        }
        
        $success = true;
        foreach ($registrations as $reg) {
            $this->unpublishSingleRegistration($reg['id_registration']);
            
            if (!Db::getInstance()->update('race_registrations', 
                ['validated' => 0, 'date_upd' => date('Y-m-d H:i:s')], 
                'id_registration = '.(int)$reg['id_registration']
            )) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    private function publishSingleRegistration($regId)
    {
        $validated = Db::getInstance()->getValue('
            SELECT validated 
            FROM `'._DB_PREFIX_.'race_registrations` 
            WHERE id_registration = '.(int)$regId
        );
        
        if (!$validated) {
            return false;
        }
        
        return Db::getInstance()->update('race_registrations', 
            ['published' => 1, 'date_upd' => date('Y-m-d H:i:s')], 
            'id_registration = '.(int)$regId
        );
    }
    
    private function unpublishSingleRegistration($regId)
    {
        return Db::getInstance()->update('race_registrations', 
            ['published' => 0, 'date_upd' => date('Y-m-d H:i:s')], 
            'id_registration = '.(int)$regId
        );
    }

    private function processConfiguration($id_product)
    {
        if (!Tools::isSubmit('submitSettings')) {
            return '';
        }

        $display_fields = [];
        
        if (Tools::isSubmit('display_fields')) {
            foreach (Tools::getValue('display_fields', []) as $field) {
                $parts = explode('|', $field);
                if (count($parts) === 2) {
                    $display_fields[$parts[0]] = (int)$parts[1];
                }
            }
        }
        
        $config = [
    'display_fields' => $display_fields,
    'category_field' => Tools::getValue('category_field'),
    'list_field' => Tools::getValue('list_field'),
    'settings' => []
];
        
        return $this->saveRaceConfiguration($id_product, $config) ?
            $this->displayConfirmation($this->l('Configuración guardada correctamente')) :
            $this->displayError($this->l('Error al guardar la configuración'));
    }

    private function processPublications()
    {
        if (!Tools::isSubmit('publishRegistrations') && !Tools::isSubmit('publishSelectedOrders')) {
            return '';
        }

        $toPublish = [];
        if (Tools::isSubmit('publishRegistrations')) {
            $toPublish = Tools::getValue('publish', []);
        } else if (Tools::isSubmit('publishSelectedOrders')) {
            $orderIds = Tools::getValue('order_ids', []);
            if (!empty($orderIds)) {
                $registrations = Db::getInstance()->executeS('
                    SELECT id_registration 
                    FROM `'._DB_PREFIX_.'race_registrations` 
                    WHERE id_order IN ('.implode(',', array_map('intval', $orderIds)).')
                    AND validated = 1 AND published = 0
                ');
                
                if (!empty($registrations)) {
                    foreach ($registrations as $reg) {
                        $toPublish[] = $reg['id_registration'];
                    }
                }
            }
        }
        
        if (empty($toPublish)) {
            return $this->displayError($this->l('No hay inscripciones válidas seleccionadas para publicar'));
        }

        $result = Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'race_registrations`
            SET published = 1, date_upd = "'.date('Y-m-d H:i:s').'"
            WHERE id_registration IN ('.implode(',', array_map('intval', $toPublish)).')
        ');
        
        return $result ?
            $this->displayConfirmation($this->l('Inscripciones marcadas como publicadas correctamente')) :
            $this->displayError($this->l('Error al marcar las inscripciones como publicadas'));
    }

    private function unpublishSelectedOrders()
    {
        if (!Tools::isSubmit('unpublishSelectedOrders')) {
            return '';
        }
        
        $orderIds = Tools::getValue('order_ids', []);
        if (empty($orderIds)) {
            return $this->displayError($this->l('Selecciona al menos un pedido para anular la publicación'));
        }
        
        $registrations = Db::getInstance()->executeS('
            SELECT id_registration 
            FROM `'._DB_PREFIX_.'race_registrations` 
            WHERE id_order IN ('.implode(',', array_map('intval', $orderIds)).')
            AND published = 1
        ');
        
        if (empty($registrations)) {
            return $this->displayWarning($this->l('No hay inscripciones publicadas para anular'));
        }
        
        $regIds = array_column($registrations, 'id_registration');
        
        $result = Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'race_registrations`
            SET published = 0, date_upd = "'.date('Y-m-d H:i:s').'"
            WHERE id_registration IN ('.implode(',', $regIds).')
        ');
        
        return $result ?
            $this->displayConfirmation(sprintf($this->l('Se ha anulado la publicación de %d inscripciones correctamente'), count($regIds))) :
            $this->displayError($this->l('Error al anular la publicación de las inscripciones'));
    }

    private function unvalidateSelectedOrders()
    {
        if (!Tools::isSubmit('unvalidateSelectedOrders')) {
            return '';
        }
        
        $orderIds = Tools::getValue('order_ids', []);
        if (empty($orderIds)) {
            return $this->displayError($this->l('Selecciona al menos un pedido para anular la validación'));
        }
        
        $registrations = Db::getInstance()->executeS('
            SELECT id_registration, id_order
            FROM `'._DB_PREFIX_.'race_registrations` 
            WHERE id_order IN ('.implode(',', array_map('intval', $orderIds)).')
            AND validated = 1
        ');
        
        if (empty($registrations)) {
            return $this->displayWarning($this->l('No hay inscripciones validadas para anular'));
        }
        
        $unvalidated = 0;
        foreach ($registrations as $reg) {
            $this->unpublishSingleRegistration($reg['id_registration']);
            
            if (Db::getInstance()->update('race_registrations', 
                ['validated' => 0, 'date_upd' => date('Y-m-d H:i:s')], 
                'id_registration = '.(int)$reg['id_registration']
            )) {
                $unvalidated++;
            }
        }
        
        return $unvalidated > 0 ?
            $this->displayConfirmation(sprintf($this->l('Se ha anulado la validación de %d inscripciones correctamente'), $unvalidated)) :
            $this->displayError($this->l('Error al anular la validación de las inscripciones'));
    }

    private function processValidateSelectedOrders()
    {
        if (!Tools::isSubmit('processValidateSelectedOrders')) {
            return '';
        }
        
        $orderIds = Tools::getValue('order_ids', []);
        if (empty($orderIds)) {
            return $this->displayError($this->l('Selecciona al menos un pedido para procesar y validar'));
        }
        
        $processed = 0;
        $errors = 0;
        
        foreach ($orderIds as $orderId) {
            if ($this->processAndValidateSingleOrder($orderId)) {
                $processed++;
            } else {
                $errors++;
            }
        }
        
        if ($processed > 0) {
            return $this->displayConfirmation(sprintf($this->l('Se han procesado y validado %d pedidos correctamente'), $processed));
        } elseif ($errors > 0) {
            return $this->displayError($this->l('Ha ocurrido un error al procesar y validar los pedidos seleccionados'));
        } else {
            return $this->displayWarning($this->l('No se ha realizado ninguna acción'));
        }
    }

    private function getTemplateVariables($id_product = null)
    {
        $page = (int)Tools::getValue('page', 1);
        if ($page < 1) $page = 1;
        
        $items_per_page = (int)Tools::getValue('items_per_page', 15);
        if (!in_array($items_per_page, [10, 15, 25, 50, 100])) {
            $items_per_page = 15;
        }
        
        $filters = [
            'reference' => Tools::getValue('filter_reference', ''),
            'state' => (int)Tools::getValue('filter_state', 0),
            'validated' => Tools::getValue('filter_validated', ''),
            'published' => Tools::getValue('filter_published', ''),
            'date_from' => Tools::getValue('filter_date_from', ''),
            'date_to' => Tools::getValue('filter_date_to', ''),
            'id_product' => $id_product,
            'race_status' => Tools::getValue('filter_race_status', 'active')
        ];
        
        $result = $this->getAllRegistrations($page, $items_per_page, $filters);
        $registrations = $result['registrations'];
        
        // Obtener configuración de la carrera
        $raceConfig = $this->getRaceConfiguration($id_product);
        
        // Obtener campos disponibles
        $available_fields = $this->getAvailableFields($id_product);
        asort($available_fields);
        
        // Obtener estados de pedidos
        $order_states = OrderState::getOrderStates($this->context->language->id);
        
        // Obtener carreras disponibles
        $race_products = $this->getRaceProducts();
        
        // Obtener estadísticas de la carrera actual
        $race_stats = null;
        if ($id_product) {
            $stats = $this->getRaceStatistics($id_product);
            if (!empty($stats)) {
                $race_stats = $stats[0];
            }
        }

        return [
    'registrations' => $registrations,
    'display_fields' => $raceConfig['display_fields'],
    'category_field' => $raceConfig['category_field'],
    'list_field' => $raceConfig['list_field'],
    'available_fields' => $available_fields,
    'module_dir' => $this->_path,
    'token' => Tools::getAdminTokenLite('AdminModules'),
    'has_registrations' => !empty($registrations),
    'pagination' => [
        'total' => $result['total'],
        'pages' => $result['pages'],
        'current' => $page,
        'items_per_page' => $items_per_page
    ],
    'filters' => $filters,
    'order_states' => $order_states,
    'action_url' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name,
    'adminOrderUrl' => $this->context->link->getAdminLink('AdminOrders'),
    'ajax_url' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&ajax=1&action=process_action',
    'race_products' => $race_products,
    'selected_product' => $id_product,
    'race_stats' => $race_stats
];
    }
    

    private function getRaceStatistics($id_product = null)
    {
        $where = '';
        if ($id_product) {
            $where = ' AND r.id_product = '.(int)$id_product;
        }
        
        return Db::getInstance()->executeS('
            SELECT 
                r.id_product,
                pl.name as race_name,
                COUNT(DISTINCT r.id_registration) as total_registrations,
                SUM(CASE WHEN r.validated = 1 THEN 1 ELSE 0 END) as validated,
                SUM(CASE WHEN r.published = 1 THEN 1 ELSE 0 END) as published,
                MIN(r.date_add) as first_registration,
                MAX(r.date_add) as last_registration,
                r.race_status,
                r.archive_date
            FROM `'._DB_PREFIX_.'race_registrations` r
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
                r.id_product = pl.id_product 
                AND pl.id_lang = '.(int)$this->context->language->id.'
            )
            WHERE 1 '.$where.'
            GROUP BY r.id_product
            ORDER BY last_registration DESC
        ');
    }

    private function getAvailableFields($id_product = null)
    {
        $fields = Db::getInstance()->executeS('
            SELECT DISTINCT pc.field_name, pfl.name as field_label
            FROM `'._DB_PREFIX_.'an_productfields_cart` pc
            LEFT JOIN `'._DB_PREFIX_.'an_productfields` pf ON (pc.id_an_productfields = pf.id_an_productfields)
            LEFT JOIN `'._DB_PREFIX_.'an_productfields_lang` pfl ON (
                pf.id_an_productfields = pfl.id_an_productfields 
                AND pfl.id_lang = '.(int)$this->context->language->id.'
            )
            LIMIT 100
        ');
        
        $result = [];
        foreach ($fields as $field) {
            if (!empty($field['field_name'])) {
                $name = !empty($field['field_label']) ? $field['field_label'] : $field['field_name'];
                $result[$field['field_name']] = $name;
            }
        }
        
        if (empty($result)) {
            $result = [
                'dorsal' => 'Dorsal',
                'nombre' => 'Nombre',
                'categoria' => 'Categoría',
                'equipo' => 'Equipo',
                'moto' => 'Moto',
                'licencia' => 'Licencia'
            ];
        }
        
        return $result;
    }

    private function getAllRegistrations($page = 1, $limit = 15, $filters = [])
    {
        $sql = '
            SELECT 
                o.id_order,
                o.reference,
                o.date_add,
                c.firstname,
                c.lastname,
                os.name as order_state,
                r.id_registration,
                r.validated,
                r.published,
                r.field_data,
                r.id_product,
                r.race_status,
                pl.name as race_name,
                (SELECT COUNT(*) FROM `'._DB_PREFIX_.'race_registrations` r2 
                 WHERE r2.id_order = o.id_order) as registration_count
            FROM `'._DB_PREFIX_.'orders` o
            LEFT JOIN `'._DB_PREFIX_.'customer` c ON o.id_customer = c.id_customer
            LEFT JOIN `'._DB_PREFIX_.'order_state_lang` os ON (
                o.current_state = os.id_order_state 
                AND os.id_lang = '.(int)$this->context->language->id.'
            )
            LEFT JOIN `'._DB_PREFIX_.'race_registrations` r ON o.id_order = r.id_order
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
                r.id_product = pl.id_product 
                AND pl.id_lang = '.(int)$this->context->language->id.'
            )
            WHERE 1';
            
        // Filtro por producto/carrera
        if (!empty($filters['id_product'])) {
            $sql .= ' AND (r.id_product = '.(int)$filters['id_product'].' OR (r.id_product IS NULL AND EXISTS (
                SELECT 1 FROM `'._DB_PREFIX_.'order_detail` od 
                WHERE od.id_order = o.id_order 
                AND od.product_id = '.(int)$filters['id_product'].'
            )))';
        }
        
        // Filtro por estado de carrera - MEJORADO para mostrar todas las inscripciones de la carrera seleccionada
        if (empty($filters['id_product'])) {
            // Solo si no hay producto seleccionado, aplicar filtro de estado
            if (!empty($filters['race_status'])) {
                if ($filters['race_status'] == 'active') {
                    $sql .= ' AND (r.race_status = "active" OR r.race_status IS NULL)';
                } else {
                    $sql .= ' AND r.race_status = "'.pSQL($filters['race_status']).'"';
                }
            } else {
                $sql .= ' AND (r.race_status = "active" OR r.race_status IS NULL)';
            }
        }
        
        // Filtros existentes
        if (!empty($filters['reference'])) {
            $sql .= ' AND o.reference LIKE "%'.pSQL($filters['reference']).'%"';
        }
        
        if (!empty($filters['state'])) {
            $sql .= ' AND o.current_state = '.(int)$filters['state'];
        }
        
        if ($filters['validated'] !== '') {
            if ($filters['validated'] == '1') {
                $sql .= ' AND r.validated = 1';
            } elseif ($filters['validated'] == '0') {
                $sql .= ' AND (r.validated = 0 OR r.validated IS NULL)';
            }
        }
        
        if ($filters['published'] !== '') {
            if ($filters['published'] == '1') {
                $sql .= ' AND r.published = 1';
            } elseif ($filters['published'] == '0') {
                $sql .= ' AND (r.published = 0 OR r.published IS NULL)';
            }
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= ' AND o.date_add >= "'.pSQL($filters['date_from']).' 00:00:00"';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= ' AND o.date_add <= "'.pSQL($filters['date_to']).' 23:59:59"';
        }
        
        $total = Db::getInstance()->getValue('SELECT COUNT(*) FROM (' . $sql . ') as t');
        
        $sql .= ' ORDER BY o.date_add DESC';
        $sql .= ' LIMIT '.(($page - 1) * $limit).', '.$limit;
        
        $registrations = Db::getInstance()->executeS($sql);
        
        if (!empty($registrations)) {
            foreach ($registrations as &$reg) {
                if (!empty($reg['field_data'])) {
                    $reg['field_data'] = json_decode($reg['field_data'], true);
                }
                
                if (!is_array($reg['field_data'])) {
                    $reg['field_data'] = [];
                }
                
                // Asignar producto si no tiene
                if (empty($reg['id_product']) && !empty($reg['id_order'])) {
                    $reg['id_product'] = $this->getRaceProductFromOrder($reg['id_order']);
                }
            }
        }
        
        return [
            'registrations' => $registrations ?: [],
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    public function getPrestashopHtml($id_product = null)
    {
        $where = 'WHERE validated = 1';
        if ($id_product) {
            $where .= ' AND id_product = '.(int)$id_product;
        }
        
        $registrations = Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'race_registrations`
            '.$where.'
            ORDER BY id_product, date_add
        ');
        
        if (empty($registrations)) {
            return '<div class="alert alert-info">No hay inscripciones validadas para exportar</div>';
        }
        
        foreach ($registrations as &$reg) {
            $reg['field_data'] = json_decode($reg['field_data'], true);
        }
        
        return $this->generatePrestashopHtml($registrations, $id_product);
    }
    
    private function generatePrestashopHtml($registrations, $id_product = null)
    {
        $config = $this->getRaceConfiguration($id_product);
        $category_field = $config['category_field'];
        $display_fields = $config['display_fields'];
        
        uasort($display_fields, function($a, $b) {
            return $a - $b;
        });
        $ordered_fields = array_keys($display_fields);

        $grouped = [];
        foreach ($registrations as $reg) {
            $category = isset($reg['field_data'][$category_field]) ? $reg['field_data'][$category_field] : 'Sin categoría';
            $grouped[$category][] = $reg;
        }

        $html = '';
        foreach ($grouped as $category => $items) {
            $html .= '<h2>' . htmlspecialchars($category) . '</h2>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-bordered table-hover">';
            $html .= '<thead><tr>';
            $html .= '<th class="text-center" style="width:40px;">#</th>';
            foreach ($ordered_fields as $field) {
                $html .= '<th>' . htmlspecialchars($field) . '</th>';
            }
            $html .= '<th>Inscrito</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';

            $counter = 1;
            foreach ($items as $item) {
                $html .= '<tr>';
                $html .= '<td class="text-center">' . $counter . '</td>';
                foreach ($ordered_fields as $field) {
                    $value = isset($item['field_data'][$field]) ? htmlspecialchars($item['field_data'][$field]) : '';
                    $html .= '<td>' . $value . '</td>';
                }
                if ($item['published']) {
                    $html .= '<td class="inscrito-cell"><span class="inscrito-badge inscrito">Inscrito</span></td>';
                } else {
                    $html .= '<td class="inscrito-cell"><span class="inscrito-badge no-inscrito">No inscrito</span></td>';
                }
                $html .= '</tr>';
                $counter++;
            }
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }

        return $html;
    }
    
    public function downloadPrestashopHtml($id_product = null)
    {
        $html = $this->getPrestashopHtml($id_product);
        
        $raceName = '';
        if ($id_product) {
            $raceName = Db::getInstance()->getValue('
                SELECT name FROM `'._DB_PREFIX_.'product_lang`
                WHERE id_product = '.(int)$id_product.'
                AND id_lang = '.(int)$this->context->language->id
            );
        }
        
        $css = '
<style>
.table {
    width: 100%;
    max-width: 100%;
    margin-bottom: 20px;
}
.table-responsive {
    min-height: .01%;
    overflow-x: auto;
}
.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #f9f9f9;
}
.table-bordered {
    border: 1px solid #ddd;
}
.table-bordered > thead > tr > th,
.table-bordered > tbody > tr > th,
.table-bordered > tfoot > tr > th,
.table-bordered > thead > tr > td,
.table-bordered > tbody > tr > td,
.table-bordered > tfoot > tr > td {
    border: 1px solid #ddd;
}
.table-hover > tbody > tr:hover {
    background-color: #f5f5f5;
}
.text-center {
    text-align: center;
}
h2 {
    font-size: 24px;
    margin-top: 20px;
    margin-bottom: 10px;
    font-weight: 500;
    color: #333;
}
th {
    text-align: left;
    font-weight: bold;
    background-color: #f7f7f7;
    padding: 8px;
}
td {
    padding: 8px;
}
.inscrito-cell {
    text-align: center;
}
.inscrito-badge {
    padding: 3px 6px;
    border-radius: 3px;
    display: inline-block;
}
.inscrito-badge.inscrito {
    background-color: #5cb85c;
    color: white;
}
.inscrito-badge.no-inscrito {
    background-color: #f0f0f0;
    color: #777;
}
</style>
';
        
        $title = 'Inscripciones de Carrera';
        if ($raceName) {
            $title .= ' - ' . $raceName;
        }
        
        $fullHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    ' . $css . '
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <h1>' . $title . '</h1>
                <p>Fecha de exportación: ' . date('d/m/Y H:i:s') . '</p>
                ' . $html . '
            </div>
        </div>
    </div>
</body>
</html>';
        
        $filename = 'inscripciones_';
        if ($raceName) {
            $filename .= Tools::str2url($raceName) . '_';
        }
        $filename .= date('Ymd_His') . '.html';
        
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($fullHtml));
        
        echo $fullHtml;
        exit;
    }

    public function downloadCsv($id_product = null)
    {
        $where = 'WHERE validated = 1';
        if ($id_product) {
            $where .= ' AND id_product = '.(int)$id_product;
        }
        
        $registrations = Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'race_registrations`
            '.$where.'
            ORDER BY id_product, date_add
        ');
        
        if (empty($registrations)) {
            echo 'No hay inscripciones validadas para exportar';
            exit;
        }
        
        $config = $this->getRaceConfiguration($id_product);
        $category_field = $config['category_field'];
        $display_fields = $config['display_fields'];
        
        uasort($display_fields, function($a, $b) {
            return $a - $b;
        });
        $ordered_fields = array_keys($display_fields);

        $grouped = [];
        foreach ($registrations as $reg) {
            $reg['field_data'] = json_decode($reg['field_data'], true);
            $category = isset($reg['field_data'][$category_field]) ? $reg['field_data'][$category_field] : 'Sin categoría';
            $grouped[$category][] = $reg;
        }
        
        $raceName = '';
        if ($id_product) {
            $raceName = Db::getInstance()->getValue('
                SELECT name FROM `'._DB_PREFIX_.'product_lang`
                WHERE id_product = '.(int)$id_product.'
                AND id_lang = '.(int)$this->context->language->id
            );
        }
        
        $filename = 'inscripciones_';
        if ($raceName) {
            $filename .= Tools::str2url($raceName) . '_';
        }
        $filename .= date('Ymd_His') . '.csv';
        
        $output = fopen('php://output', 'w');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        foreach ($grouped as $category => $items) {
            fputcsv($output, ['Categoría: ' . $category], ';');
            
            $headers = ['#'];
            foreach ($ordered_fields as $field) {
                $headers[] = $field;
            }
            $headers[] = 'Inscrito';
            fputcsv($output, $headers, ';');
            
            $counter = 1;
            foreach ($items as $item) {
                $row = [$counter];
                foreach ($ordered_fields as $field) {
                    $row[] = isset($item['field_data'][$field]) ? $item['field_data'][$field] : '';
                }
                $row[] = $item['published'] ? 'Inscrito' : 'No inscrito';
                fputcsv($output, $row, ';');
                $counter++;
            }
            
            fputcsv($output, [''], ';');
        }
        
        fclose($output);
        exit;
    }
    
    public function getFieldFromRegistration($registration, $field_name = '')
    {
        if (empty($field_name) || !isset($registration['field_data']) || !is_array($registration['field_data'])) {
            return '';
        }
        
        return isset($registration['field_data'][$field_name]) ? $registration['field_data'][$field_name] : '';
    }
}