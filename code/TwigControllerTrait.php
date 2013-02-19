<?php

trait TwigControllerTrait
{

    public function __get($name)
    {
        if ($name == 'dic') {
            return $this->dic = new TwigContainer;
        } else {
            return parent::__get($name);
        }
    }

    public function __isset($name)
    {
        return $this->hasMethod($name) ? false : true;
    }

    public function handleAction($request, $action) {
        foreach($request->latestParams() as $k => $v) {
            if($v || !isset($this->urlParams[$k])) $this->urlParams[$k] = $v;
        }

        $this->action = $action;
        $this->requestParams = $request->requestVars();

        if($this->hasMethod($action)) {
            $result = parent::handleAction($request, $action);

            // If the action returns an array, customise with it before rendering the template.
            if(is_array($result)) {
                //return $this->getViewer($action)->process($this->customise($result));
                return $this->renderTwig($this->getTemplateList($action), $this->customise($result));
            } else {
                return $result;
            }
        } else {
            //return $this->getViewer($action)->process($this);
            return $this->renderTwig($this->getTemplateList($action), $this);
        }
    }

    public function renderWith($templates, $customFields = null)
    {
        $data = ($this->customisedObject) ? $this->customisedObject : $this;

        if (is_array($customFields) || $customFields instanceof ViewableData) {
            $data = $data->customise($customFields);
        }

        if (!is_array($templates)) {
            $templates = array($templates);
        }

        return $this->renderTwig($templates, $data);
    }

    public function render($params = null)
    {
        $obj = ($this->customisedObj) ? $this->customisedObj : $this;
        if ($params) {
            $obj = $this->customise($params);
        }

        return $this->renderTwig($this->getTemplateList($this->getAction()), $obj);
    }

    protected function renderTwig($templates, $context)
    {
        return $this->getTwigTemplate($templates)->render(array(
            $this->dic['twig.controller_variable_name'] => $context
        ));
    }

    public function customise($params)
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    protected function getTwigTemplate($templates)
    {
        $loader = $this->dic['twig.loader'];
        $extensions = $this->dic['twig.extensions'];
        foreach ($templates as $value) {
            foreach ($extensions as $extension) {
                if ($loader->exists($value . $extension)) {
                    return $this->dic['twig']->loadTemplate($value . $extension);
                }
            }
        }
        throw new InvalidArgumentException("No templates for " . implode(', ', $templates) . " exist");
    }

    protected function getTemplateList($action = null)
    {
        // Hard-coded templates
        if ($this->templates[$action]) {
            $templates = $this->templates[$action];
        } elseif ($this->templates['index']) {
            $templates = $this->templates['index'];
        } elseif ($this->template) {
            $templates = $this->template;
        } else {
            // Add action-specific templates for inheritance chain
            $parentClass = $this->class;
            if ($action && $action != 'index') {
                $parentClass = $this->class;
                while ($parentClass != "Controller") {
                    $templates[] = strtok($parentClass,'_') . '_' . $action;
                    $parentClass = get_parent_class($parentClass);
                }
            }
            // Add controller templates for inheritance chain
            $parentClass = $this->class;
            while ($parentClass != "Controller") {
                $templates[] = strtok($parentClass,'_');
                $parentClass = get_parent_class($parentClass);
            }

            // remove duplicates
            $templates = array_unique($templates);
        }

        return $templates;
    }

}
