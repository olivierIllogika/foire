<?php
  /* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Authors: Tristan Zajonc  <tristanz@gmail.com>                        |
// | Credits: This class, its code and documentation, is an almost        |
// |          verbatim copy on the javascript helper functions in the     |
// |          Ruby on Rails framework.  RoR is MIT licensed, thank god.   |
// |          The actual javascript is provided by the amazing Prototype  |
// |          library.                                                    |
// |                                                                      |
// |          Ruby on Rails: http://www.rubyonrails.org                   |
// |          Prototype: http://prototype.conio.net                       |
// +----------------------------------------------------------------------+
//
// \$Id:

/* I guess I have to put this here...  Basically do whatever you want.
/*
* The MIT License
*
* Copyright (c) 2005 Tristan Zajonc
* Original Ruby Implementation: Copyright (c) 2004 David Heinemeier Hansson
*
* Permission is hereby granted, free of charge, to any person obtaining a
* copy of this software and associated documentation files (the "Software"),
* to deal in the Software without restriction, including without limitation
* the rights to use, copy, modify, merge, publish, distribute, sublicense,
* and/or sell copies of the Software, and to permit persons to whom the
* Software is furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
* FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
* DEALINGS IN THE SOFTWARE.
*
*/

    class HtmlAjaxHelper extends HtmlHelper {

        /**
        * Returns a javascript script tag
        *
        * @param string $script the javascript
        * @return string inline javascript
        */
        function javascriptTag($script) {
            return sprintf('<script language="javascript" type="text/javascript">%s</script>', $script);
        }
        /**
        * Returns a javascript include tag
        *
        * @param string $url url to javascript file.
        * @return string javascript include tag
        */
        function javascriptIncludeTag($url) {
            return sprintf('<script language="javascript" type="text/javascript" src="%s"></script>', $url);
        }

        /**
        * Returns link to javascript function
        *
        * Returns a link that'll trigger a javascript function using the
        * onclick handler and return false after the fact.
        *
        * Examples:
        * <code>
        *   linkToFunction("Greeting", "alert('Hello world!')");
        *   linkToFunction(imageTag("delete"), "if confirm('Really?'){ do_delete(); }");
        * </code>
        *
        * @param string $title title of link
        * @param string $func javascript function to be called on click
        * @param array $html_options html options for link
        * @return string html code for link to javascript function
        */
        function linkToFunction($title, $func, $html_options = null) {
            $html_options['onClick'] = "$func; return false;";
            return $this->linkTo($title, '#', $html_options);
        }
        /**
        * Returns link to remote action
        *
        * Returns a link to a remote action defined by <i>options[url]</i>
        * (using the urlFor format) that's called in the background using
        * XMLHttpRequest. The result of that request can then be inserted into a
        * DOM object whose id can be specified with <i>options[update]</i>.
        * Usually, the result would be a partial prepared by the controller with
        * either renderPartial or renderPartialCollection.
        *
        * Examples:
        * <code>
        *  linkToRemote("Delete this post",
        *          array("update" => "posts", "url" => "delete/{$postid->id}"));
        *  linkToRemote(imageTag("refresh"),
        *        array("update" => "emails", "url" => "list_emails" ));
        * </code>
        *
        * By default, these remote requests are processed asynchronous during
        * which various callbacks can be triggered (for progress indicators and
        * the likes).
        *
        * Example:
        * <code>
        *   linkToRemote (word,
        *       array("url" => "undo", "n" => word_counter),
        *       array("complete" => "undoRequestCompleted(request)"));
        * </code>
        *
        * The callbacks that may be specified are:
        *
        * - <i>loading</i>::       Called when the remote document is being
        *                           loaded with data by the browser.
        * - <i>loaded</i>::        Called when the browser has finished loading
        *                           the remote document.
        * - <i>interactive</i>::   Called when the user can interact with the
        *                           remote document, even though it has not
        *                           finished loading.
        * - <i>complete</i>::      Called when the XMLHttpRequest is complete.
        *
        * If you for some reason or another need synchronous processing (that'll
        * block the browser while the request is happening), you can specify
        * <i>options[type] = synchronous</i>.
        *
        * You can customize further browser side call logic by passing
        * in Javascript code snippets via some optional parameters. In
        * their order of use these are:
        *
        * - <i>confirm</i>::      Adds confirmation dialog.
        * - <i>condition</i>::    Perform remote request conditionally
        *                          by this expression. Use this to
        *                          describe browser-side conditions when
        *                          request should not be initiated.
        * - <i>before</i>::       Called before request is initiated.
        * - <i>after</i>::        Called immediately after request was
        *                       initiated and before <i>loading</i>.
        *
        * @param string $title title of link
        * @param array $options options for javascript function
        * @param array $html_options options for link
        * @return string html code for link to remote action
        */
        function linkToRemote($title, $options = null, $html_options = null) {
            return $this->linkToFunction($title, $this->remoteFunction($options), $html_options);
        }
        /**
        * Creates javascript function for remote AJAX call
        *
        * This function creates the javascript needed to make a remote call
        * it is primarily used as a helper for linkToRemote.
        *
        * @see linkToRemote() for docs on options parameter.
        *
        * @param array $options options for javascript
        * @return string html code for link to remote action
        */
        function remoteFunction($options = null) {
            $javascript_options = $this->__optionsForAjax($options);
            $func = isset($options['update']) ? "new Ajax.Updater('{$options['update']}', " : "new Ajax.Request(";
            $func.= "'".$this->urlFor($options['url']) ."'";
            $func.= ", $javascript_options)";
            if (isset($options['before'])) $func.= "{$options['before']}; $function";
            if (isset($options['after'])) $func.= "$func; {$options['before']};";
            if (isset($options['condition'])) $func.= "if ({$options['condition']}) { $func; }";
            if (isset($options['confirm'])) $func.= "if (confirm('".$this->escapeJavascript($options['confirm']) ."')) { $func; }";
            return $func;
        }
        /**
        * Escape carrier returns and single and double quotes for Javascript segments.
        *
        * @param string $javascript string that might have javascript elements
        * @return string escaped string
        */
        function escapeJavascript($javascript) {
            $javascript = str_replace(array("\r\n", "\n", "\r"), '\n', $javascript);
            $javascript = str_replace(array('"', "'"), array('\"', "\\'"), $javascript);
            return $javascript;
        }
        /**
        * Periodically call remote url via AJAX.
        *
        * Periodically calls the specified url (<i>options[url]</i>) every <i>options[frequency]</i> seconds (default is 10).
        * Usually used to update a specified div (<i>options[update]</i>) with the results of the remote call.
        * The options for specifying the target with url and defining callbacks is the same as linkToRemote.
        *
        * @param array $options callback options
        * @return string javascript code
        */
        function periodicallyCallRemote($options = null) {
            $frequency = (isset($options['frequency'])) ? $options['frequency'] : 10;
            $code = "new PeriodicalExecuter(function() {".$this->remote_function($options) ."}, $frequency)";
            return $this->javascriptTag($code);
        }
        /**
        * Returns form tag that will submit using Ajax.
        *
        * Returns a form tag that will submit using XMLHttpRequest in the background instead of the regular
        * reloading POST arrangement. Even though it's using Javascript to serialize the form elements, the form submission
        * will work just like a regular submission as viewed by the receiving side (all elements available in params).
        * The options for specifying the target with :url and defining callbacks is the same as link_to_remote.
        *
        * @param array $options callback options
        * @return string javascript code
        */
        function formRemoteTag($options = null) {
            $options['form'] = true;
            $options['html']['onsubmit'] = $this->remoteFunction($options) ."; return false;";
            return $this->tag("form", $options['html'], true);
        }
        /**
        * Returns a button input tag that will submit using Ajax
        *
        * Returns a button input tag that will submit form using XMLHttpRequest in the background instead of regular
        * reloading POST arrangement. <i>options</i> argument is the same as in <i>form_remote_tag</i>
        *
        * @param string $name input button name
        * @param string $value input button value
        * @param array $options callback options
        * @return string ajaxed input button
        */
        function submitToRemote($name, $value, $options = null) {
            $options['with'] = 'Form.serialize(this.form)';
            $options['html']['type'] = 'button';
            $options['html']['onclick'] = $this->remoteFunction($options) ."; return false;";
            $options['html']['name'] = $name;
            $options['html']['value'] = $value;
            return $this->tag("input", $options['html'], false);
        }
        /**
        * Includes the Prototype Javascript library (and anything else) inside a single script tag
        *
        * Note: The recommended approach is to copy the contents of
        * lib/javascripts/ into your application's
        * public/javascripts/ directory, and use @see javascriptIncludeTag() to
        * create remote script links.
        * @return string script with all javascript in /javascripts folder
        */
        function defineJavascriptFunctions() {
            $dir = dirname(__FILE__) ."/javascripts";
            $files = scandir($dir);
            $javascript = '';
            foreach($files as $file) {
                if (substr($file, -3) == '.js') {
                    $javascript.= file_get_contents("$dir/$file") ."\n\n";
                }
            }
            return $this->javascriptTag($javascript);
        }
        /**
        * Observe field and call ajax on change.
        *
        * Observes the field with the DOM ID specified by <i>field_id</i> and makes
        * an Ajax when its contents have changed.
        *
        * Required +options+ are:
        * - <i>frequency</i>:: The frequency (in seconds) at which changes to
        *                       this field will be detected.
        * - <i>url</i>::       @see urlFor() -style options for the action to call
        *                       when the field has changed.
        *
        * Additional options are:
        * - <i>update</i>::    Specifies the DOM ID of the element whose
        *                       innerHTML should be updated with the
        *                       XMLHttpRequest response text.
        * - <i>with</i>::      A Javascript expression specifying the
        *                       parameters for the XMLHttpRequest. This defaults
        *                       to Form.Element.serialize('$field_id'), which can be
        *                       accessed from params['form']['field_id'].
        *
        * Additionally, you may specify any of the options documented in
        * @see linkToRemote().
        *
        * @param string $field_id DOM ID of field to observe
        * @param array $options ajax options
        * @return string ajax script
        */
        function observeField($field_id, $options = null) {
            if (!isset($options['with'])) $options['with'] = "Form.Element.serialize('$field_id')";
            return $this->__buildObserver('Form.Element.Observer', $field_id, $options);
        }
        /**
        * Observe entire form and call ajax on change.
        *
        * Like @see observeField(), but operates on an entire form identified by the
        * DOM ID <b>form_id</b>. <b>options</b> are the same as <b>observe_field</b>, except
        * the default value of the <i>with</i> option evaluates to the
        * serialized (request string) value of the form.
        *
        * @param string $field_id DOM ID of field to observe
        * @param array $options ajax options
        * @return string ajax script
        */
        function observeForm($field_id, $options = null) {
            //i think this is a rails bug... should be set
            if (!isset($options['with'])) $options['with'] = 'Form.serialize(this.form)';
            return $this->__buildObserver('Form.Observer', $field_id, $options);
        }
        /**
        * Javascript helper function (private).
        *
        */
        function __optionsForAjax($options) {
            $js_options = $this->__buildCallbacks($options);
            $js_options['asynchronous'] = 'true';
            if (isset($options['type'])) {
                if ($options['type'] == 'synchronous') $js_options['asynchronous'] = 'false';
            }
            if (isset($options['method'])) $js_options['method'] = $this->__methodOptionToString($options['method']);
            if (isset($options['position'])) $js_options['insertion'] = "Insertion.".Inflector::camelize($options['position']);
            if (isset($options['form'])) {
                $js_options['parameters'] = 'Form.serialize(this)';
            } elseif (isset($options['with'])) {
                $js_options['parameters'] = $options['with'];
            }
            $out = array();
            foreach($js_options as $k=>$v) {
                $out[] = "$k:$v";
            }
            $out = join(', ', $out);
            $out = '{'.$out.'}';
            return $out;
        }
        function __methodOptionToString($method) {
            return (is_string($method) && !$method[0] == "'") ? $method : "'$method'";
        }
        function __buildObserver($klass, $name, $options = null) {
            if (!isset($options['with']) && isset($options['update'])) {
                $options['with'] = 'value';
            }
            $callback = $this->remoteFunction($options);
            $javascript = "new $klass('$name', ";
            $javascript.= "{$options['frequency']}, function(element, value) {";
            $javascript.= "$callback})";
            return $this->javascriptTag($javascript);
        }
        function __buildCallbacks($options) {
            $actions = array('uninitialized', 'loading', 'loaded', 'interactive', 'complete');
            $callbacks = array();
            foreach($actions as $callback) {
                if (isset($options[$callback])) {
                    $name = 'on'.ucfirst($callback);
                    $code = $options[$callback];
                    $callbacks[$name] = "function(request){".$code."}";
                }
            }
            return $callbacks;
        }

    }
?>
