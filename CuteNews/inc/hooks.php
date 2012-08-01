<?php

    if ($member_db[UDB_ACL] != ACL_LEVEL_ADMIN)
        msg("error", lang("Access Denied"), lang("You don't have permission for this section"));

    extract( filter_request('action,desc,func,order,function,hook,ord_new'), EXTR_OVERWRITE );

    // raw hooks
    function compile_hooks_file()
    {
        $raw = fopen(SERVDIR.'/cdata/hooks.php' ,'w');
        $fhooks = fopen(DB_HOOKS, 'r');
        fgets($fhooks);
        fwrite($raw, "<?php\n");

        while ($hook_data = fgets($fhooks))
        {
            list(,$e) = explode('|', $hook_data, 2);
            foreach (unserialize($e) as $i => $v)
            {
                fwrite($raw, 'function hook_'.$v['func'].'_'.$i.'($args)'."\n{\n");
                fwrite($raw, str_replace('{nl}', "\n", $v['function']));
                fwrite($raw, "\n}\n");
            }
        }

        fwrite($raw, "?>");
        fclose($raw);
    }

    if ($action == 'add-hook')
    {
        $ord = 0;
        $hook_item = bsearch_key($hook, DB_HOOKS);
        if ( is_array($hook_item) )
             while ( isset($hook_item[$ord]) ) $ord++;
        else $hook_item = array();

        // add new hook
        $hook_item[$ord] = array('func' => $hook, 'function' => false, 'desc' => $desc, 'order' => $ord);

        // replace hook
        add_key($hook, false, DB_HOOKS);
        edit_key($hook, $hook_item, DB_HOOKS);

        compile_hooks_file();
        header('Location: '.PHP_SELF.'?mod=hooks');

    }
    elseif ($action == 'do-edit')
    {
        // check for invalid
        $hookr = bsearch_key($hook, DB_HOOKS);
        if (!$hookr || !isset($hookr[$order])) header('Location: '.PHP_SELF.'?mod=hooks');

        // sanitize
        $order = (int)$order;
        $function = str_replace(array("\n\r", "\r\n", "\n"), '{nl}', $function);

        // reset
        unset($hookr[$order]);
        $hookr[$ord_new]  = array('func' => $hook, 'function' => $function, 'desc' => $desc, 'order' => $ord_new);
        edit_key($hook, $hookr, DB_HOOKS);

        compile_hooks_file();
        header('Location: '.PHP_SELF.'?mod=hooks');

    }
    elseif ($action == 'remove')
    {
        // check for invalid
        $hookr = bsearch_key($hook, DB_HOOKS);
        if (!$hookr || !isset($hookr[$order])) header('Location: '.PHP_SELF.'?mod=hooks');

        // remove
        unset($hookr[$order]);
        if ( count($hookr) )
             edit_key($hook, $hookr, DB_HOOKS);
        else delete_key($hook, DB_HOOKS);

        compile_hooks_file();
        header('Location: '.PHP_SELF.'?mod=hooks');
    }

    // -----------------------------------------------------------------------------------------------------------------

    $table_hooks = array();
    $fhooks = fopen(DB_HOOKS, 'r'); fgets($fhooks);
    while ($hook_data = fgets($fhooks))
    {
        $hooks = array();
        list(,$e) = explode('|', $hook_data, 2);
        foreach (unserialize($e) as $i => $v) $hooks[] = $v;
        if ($v['func']) $table_hooks[] = array( proc_tpl('hooks/table', array('table' => $hooks)), $v['func'] );
    }

    if ($action == 'edit')
    {
        $hookr = bsearch_key($hook, DB_HOOKS);
        if (!$hookr || !isset($hookr[$order])) header('Location: '.PHP_SELF.'?mod=hooks');

        echoheader('options', lang('Edit hook'), make_breadcrumbs('main=Main page/options/hooks=Hook/=Edit hook'));
        echo proc_tpl('hooks/edit', array('D' => $hookr[$order]['desc'],
                                          'F' => $hookr[$order]['func'],
                                          'T' => str_replace('{nl}', "\n", $hookr[$order]['function']),
                                          'O' => $hookr[$order]['order'],
                                         ));
    }
    else
    {
        echoheader('options', lang('Program hooks and plugins'), make_breadcrumbs('main=Main page/options/hooks=Hook'));
        echo proc_tpl('hooks/index', array('table' => $table_hooks), array('IS' => count($table_hooks)));

    }
    echofooter();

?>