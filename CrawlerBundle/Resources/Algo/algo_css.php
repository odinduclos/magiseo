<?php


$verbose_del_selector = false;
$verbose_merge_selector = false;
$verbose_del_rules = false;
$verbose_errors = false;
$verbose_get_css_files = false;
$verbose_bdd = false;

// gerer les imports
// gerer les medias
// verifier les caracters CSS3
// verifier les webkits etc

// TODO
// verifier real_path
// supprimer les magiseo

function isCssFile($file)
{
    return (pathinfo($file, PATHINFO_EXTENSION) == 'css');
}

/*class optiCSS extends Thread
{*/
function read_temp_dom_file($filename)
{
    return unserialize(file_get_contents($filename.'.obj'));
}

function validInstance($obj)
{
    return (method_exists($obj, 'getSelector'));
}
// selectionne chaque fichiers qui inclut le fichier css
function getHtmlFiles($filename) {
    global $selectors, $currentDirectory, $verbose_get_css_files, $rootDir;
    // var_dump($selectors);
    $htmlFiles = array();
    foreach ($selectors as $key => $node) {
        foreach ($node['includes'] as $key => $include) {
            if (strpos($include, $filename) !== false ) {
                    $htmlFiles[] = $node['filename'];
            }
        }
    }
    if ($verbose_get_css_files) var_dump($filename);
    if ($verbose_get_css_files) var_dump($node['filename']);
    if ($verbose_get_css_files) var_dump($htmlFiles);
    return $htmlFiles;
}

function deleteSelectors(&$cssDoc, $wp, &$errors, $filename)
{
    // selectors found in html
    global $verbose_del_selector, $verbose_bdd;
    global $selectors;

    // foreach block in the css file
    $getContents =  $cssDoc->getContents();
    $sizeGetContents = count($getContents);
    $htmlFiles = getHtmlFiles($filename);
    // if ($verbose_del_selector) error_log('');
    if ($verbose_del_selector) error_log('DELETE SELECTORS');
    for ($cptGetContents=0; $cptGetContents < $sizeGetContents; $cptGetContents++)
    {
        $content = $getContents[$cptGetContents];
        if (validInstance($content))
        {
            // foreach selector in block
            $getSelectors = $content->getSelectors();
            $sizeGetSelector = count($getSelectors);
            if ($verbose_del_selector) error_log('  For block ' . $content->__toString() . '');
            for ($cptGetSelector=0; $cptGetSelector < $sizeGetSelector; $cptGetSelector++)
            {
                $selector = $getSelectors[$cptGetSelector];
                // foreach selectors in html
                $found = false;
                // var_dump($htmlFiles);
                if ($verbose_del_selector) var_dump('      Search if ' . $selector->__toString() . ' exist');
                foreach ($htmlFiles as $filename)
                {
                    $html = read_temp_dom_file($filename);
                    // if selector not found in html
                    if ($verbose_del_selector) var_dump('          In ' . $filename . '');
                    if ($html->find($selector->getSelector()) != NULL)
                    {
                        $found = true;
                        if ($verbose_del_selector) var_dump('              true');
                    }
                    else
                    {
                        if ($verbose_del_selector) var_dump('              false');
                    }
                }
                // if ($verbose_del_selector) error_log('');
                if (!$found)
                {
                    if ($verbose_bdd) var_dump('Remove selector ' . $selector->getSelector());
                    $errors['remove_selector'][] = $selector->getSelector();
                    $content->removeSelector($selector);
                    if ($verbose_del_selector) error_log('      Selector deleted');
                }
                else
                {
                    $destroy_block = false;
                }
            }
            // if ($verbose_del_selector) error_log('');
            if (count($content->getSelector()) <= 0)
            {
                $cssDoc->remove($content);
                if ($verbose_del_selector) error_log('  Block deleted');
            }
        }
    }
    // if ($verbose_del_selector) error_log('');
    // gc_collect_cycles();
    return $cssDoc;
}

function optimizeCss(&$cssDoc, $wp, $filename)
{
    global $verbose_merge_selector;
    global $verbose_del_rules;
    global $verbose_errors;
    global $verbose_bdd;

    // error_log("SALUT");
    // selectors already done
    $valid = array();
    $errors = array();
    // var_dump($cssDoc);
    $cssDoc = deleteSelectors($cssDoc, $wp, $errors, $filename);

    // foreach block in the css file
    $getContents =  $cssDoc->getContents();
    $sizeGetContents = count($getContents);
    // if ($verbose_merge_selector || $verbose_del_rules) error_log('');
    if ($verbose_merge_selector || $verbose_del_rules) var_dump('MERGE SELECTORS AND DEL RULES');
    for ($cptGetContents=0; $cptGetContents < $sizeGetContents; $cptGetContents++)
    {
        if (isset($getContents[$cptGetContents]))
        {
            $content = $getContents[$cptGetContents];
            $valid[] = $content;
            if (validInstance($content))
            {
                // foreach block
                $getContentsCompare = $cssDoc->getContents();
                $sizeGetContentsCompare = count($getContentsCompare);
                if ($verbose_merge_selector) var_dump('    For block (merge) ' . $content->__toString() . '');
                for ($cptContentCompare=0; $cptContentCompare < $sizeGetContentsCompare; $cptContentCompare++)
                {
                    if (isset($getContentsCompare[$cptContentCompare]))
                    {
                        $contentCompare = $getContentsCompare[$cptContentCompare];
                        // not checked
                        if (validInstance($contentCompare) && !in_array($contentCompare, $valid, true))
                        {
                            if ($verbose_merge_selector) var_dump('        Compared with ' . $contentCompare->__toString() . '');
                            // if the selectors are the same but not the same instance
                            // if ($verbose_merge_selector)
                            if (count(array_diff($content->getSelector(), $contentCompare->getSelector())) == 0 &&
                                count($content->getSelector()) == count($contentCompare->getSelector()) &&
                                $content->getSelector() !== $contentCompare->getSelector())
                            {
                                if ($verbose_bdd) var_dump('Merge selectors ' . $contentCompare->getSelector()[0]->__toString());
                                $errors['merge_selectors'][] = $contentCompare->getSelector()[0]->__toString();
                                if ($verbose_merge_selector) var_dump('        Same block found -> Merge Selectors');
                                // foreach rules in the block
                                $getRules = $contentCompare->getRules();
                                $sizeGetRules = count($getRules);
                                for ($cptGetRules=0; $cptGetRules < $sizeGetRules; $cptGetRules++)
                                {
                                    $valueRule = $getRules[$cptGetRules];
                                    // add the rules to the first block
                                    $content->addRule($valueRule);
                                    $contentCompare->removeRule($valueRule);
                                }
                                // remove the last block
                                if ($verbose_merge_selector) var_dump('        Delete block -> ' . $contentCompare->__toString() . '');
                                $cssDoc->remove($contentCompare);
                            }
                        }
                    }
                }
                // gc_collect_cycles();
                // if ($verbose_merge_selector)  var_dump('');
                // rules already done
                $rules = array();
                // foreach rules in block
                $getRules = $content->getRules();
                $sizeGetRules = count($getRules);
                if ($verbose_del_rules) var_dump(' For block (rule) ' . $content->__toString() . '');
                for ($cptGetRules=0; $cptGetRules < $sizeGetRules; $cptGetRules++)
                {
                    $rule = $getRules[$cptGetRules];
                    $rules[] = $rule;
                    // foreach rules in block
                    $getRulesCompare = $content->getRules();
                    $sizeGetRulesCompare = count($getRules);
                    if ($verbose_del_rules) var_dump('     Compare ' . $rule->__toString() . ' with -> ');
                    for ($cptGetRulesCompare=0; $cptGetRulesCompare < $sizeGetRulesCompare; $cptGetRulesCompare++)
                    {
                        if (isset($getRulesCompare[$cptGetRulesCompare]))
                        {
                            $ruleCompare = $getRulesCompare[$cptGetRulesCompare];
                            if (!in_array($ruleCompare, $rules, true))
                            {
                                // if the rule is the same, but is not the same instance
                                // var_dump($ruleCompare->getRule() . ' == ' . $rule->getRule() . '?');
                                if ($verbose_del_rules) var_dump('' . $ruleCompare->__toString() . ' -> ');
                                if ($rule->getRule() == $ruleCompare->getRule() &&
                                    $rule !== $ruleCompare)
                                {
                                    $rules[] = $ruleCompare;
                                    if ($verbose_bdd) var_dump('Remove rule ' . $rule->__toString() . ' from selector ' . $content->__toString());
                                    $errors['remove_rule'][] = array($content->__toString(), $rule->__toString());
                                    // remove the last rule
                                    $content->removeRule($rule);
                                    if ($verbose_del_rules) var_dump('         found');
                                    break;
                                }
                                else
                                {
                                    if ($verbose_del_rules) var_dump('         not found');
                                }
                            }
                        }
                    }
                    //if ($verbose_del_rules) var_dump('');
                }
                // if ($verbose_merge_selector) var_dump('');
                // gc_collect_cycles();
            }
        }
    }
    // if ($verbose_merge_selector || $verbose_del_rules) var_dump('');
    if ($verbose_errors) var_dump("RESULTAT: ");
    if ($verbose_errors) var_dump($errors);
    // var_dump(print_r($errors, true));
    gc_collect_cycles();
    return $cssDoc;
}
?>