<?php

namespace Mrzkit\WpPluginSnippetCodeManager\Contract;

interface Plugin
{
    /**
     * @desc 安装
     * @return mixed
     */
    public function install();

    /**
     * @desc 卸载
     * @return mixed
     */
    public function uninstall();

    /**
     * @desc 激活
     * @return mixed
     */
    public function active();

    /**
     * @desc 禁用
     * @return mixed
     */
    public function disable();

    /**
     * @desc 升级
     * @return mixed
     */
    public function upgrade();
}
