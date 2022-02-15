<?php

// Protection against direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Filesystem\Path;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Installation script class.
 *
 * @category Class
 * @package  HikaShop
 * @author   ConcordPay <serhii.shylo@mustpay.tech>
 * @license  GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link     https://concordpay.concord.ua
 *
 * @since version 3.8.0
 */
class plgHikashoppaymentConcordpayInstallerScript
{
    /**
     * Plugin installation path.
     *
     * @var string
     *
     * @since version 3.8.0
     */
    protected $installPath;

    /**
     * Plugin files.
     *
     * @var mixed
     *
     * @since version 3.8.0
     */
    protected $files = [
        'concordpay.php',
        'concordpay_end.png',
        'index.html',
        'language' => [
            'en-GB' => 'en-GB.plg_hikashoppayment_concordpay.ini',
            'ru-RU' => 'ru-RU.plg_hikashoppayment_concordpay.ini',
            'uk-UA' => 'uk-UA.plg_hikashoppayment_concordpay.ini'
        ],
        'images' => [
            'payment' => [
                'cp-applepay.png',
                'cp-googlepay.png',
                'cp-mastercard.png',
                'cp-visa.png',
            ]
        ]
    ];

    /**
     * Constructor
     *
     * @param InstallerAdapter $adapter The object responsible for running this script
     *
     * @since 3.8.0
     */
    public function __construct(InstallerAdapter $adapter)
    {
        $this->installPath = JPATH_ROOT . '/plugins/hikashoppayment/concordpay';
    }

    /**
     * Called before any type of action
     *
     * @param string           $route   Which action is happening (install|uninstall|discover_install|update)
     * @param InstallerAdapter $adapter The object responsible for running this script
     *
     * @return boolean  True on success
     * @since  3.8.0
     */
    public function preflight($route, InstallerAdapter $adapter)
    {
        return true;
    }

    /**
     * Called after any type of action
     *
     * Данный метод используется вместо применения тега <media> в concordpay.xml,
     * т.к. присутствует баг, когда при деинсталляции модуля ConcordPay ошибочно
     * удаляется каталог /media/com_hikashop, в который были скопированы логотипы
     * систем оплаты.
     *
     * @param string           $route   Which action is happening (install|uninstall|discover_install|update)
     * @param InstallerAdapter $adapter The object responsible for running this script
     *
     * @return boolean True on success
     * @since  3.8.0
     */
    public function postflight($route, $adapter)
    {
        if (strtolower($route) === 'install') {
            foreach ($this->files['images']['payment'] as $filename) {
                $path_from = JPATH_SITE . "/plugins/hikashoppayment/concordpay/media/images/payment/$filename";
                $path_to = JPATH_SITE . "/media/com_hikashop/images/payment/$filename";

                if (file_exists($path_from) && !file_exists($path_to)) {
                    JFile::copy($path_from, $path_to);
                }
            }
        }

        return true;
    }

    /**
     * Called on installation
     *
     * @param InstallerAdapter $adapter The object responsible for running this script
     *
     * @return boolean  True on success
     * @throws Exception
     *
     * @since 3.8.0
     */
    public function install(InstallerAdapter $adapter)
    {
        self::makeDir(JPATH_ROOT . '/media/com_hikashop/images/payment');

        return true;
    }

    /**
     * Called on update
     *
     * @param InstallerAdapter $adapter The object responsible for running this script
     *
     * @return boolean  True on success
     * @since  3.8.0
     */
    public function update(InstallerAdapter $adapter)
    {
        return true;
    }

    /**
     * Called on uninstallation
     *
     * @param InstallerAdapter $adapter The object responsible for running this script
     *
     * @return boolean
     *
     * @since 3.8.0
     */
    public function uninstall(InstallerAdapter $adapter)
    {
        foreach ($this->files['images']['payment'] as $filename) {
            $path = JPATH_ROOT . "/media/com_hikashop/images/payment/$filename";
            if (file_exists($path) && !unlink($path)) {
                throw new \RuntimeException(sprintf('File "%s" was not deleted', $path));
            }
        }

        return true;
    }

    /**
     * Make plugin directory.
     *
     * @param $path string Directory path
     *
     * @return void
     *
     * @since version 3.8.0
     */
    public static function makeDir($path)
    {
        if (!file_exists($path)
            && !mkdir($path, 0777, true)
            && !is_dir($path)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }

    /**
     * Remove plugin directory.
     *
     * @param $dirPath string Plugin directory path.
     *
     * @return bool
     *
     * @since version 3.8.0
     */
    public static function deleteDir($dirPath)
    {
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if ($dirPath[strlen($dirPath) - 1] !== '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);

        return true;
    }
}
