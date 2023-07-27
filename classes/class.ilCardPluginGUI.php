<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * Test Page Component GUI
 * @author            Roberto Pasini <bonjour@kalamun.net>
 * @ilCtrl_isCalledBy ilCardPluginGUI: ilPCPluggedGUI
 * @ilCtrl_isCalledBy ilCardPluginGUI: ilUIPluginRouterGUI
 */
class ilCardPluginGUI extends ilPageComponentPluginGUI
{
    protected /* ilLanguage */ $lng;
    protected ilCtrl $ctrl;
    protected ilGlobalTemplateInterface $tpl;
    protected ilTree $tree;
    protected ilObjectService $object;
    protected ilObjUser $user;

    public function __construct()
    {
        global $DIC;

        parent::__construct();

        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC['tpl'];
        $this->tree = $DIC->repositoryTree();
        $this->object = $DIC->object();
        $this->user = $DIC['ilUser'];
    }

    /**
     * Execute command
     */
    public function executeCommand() : void
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                // perform valid commands
                $cmd = $this->ctrl->getCmd();
                if (in_array($cmd, array("create", "save", "edit", "update", "cancel"))) {
                    $this->$cmd();
                }
                break;
        }
    }

    /**
     * Create
     */
    public function insert() : void
    {
        $form = $this->initForm(true);
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Save new pc example element
     */
    public function create() : void
    {
        $form = $this->initForm(true);
        if ($this->saveForm($form, true)) {
            ;
        }
        {
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("msg_obj_modified"), true);
            $this->returnToParent();
        }
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    public function edit() : void
    {
        $form = $this->initForm();

        $this->tpl->setContent($form->getHTML());
    }

    public function update() : void
    {
        $form = $this->initForm(false);
        if ($this->saveForm($form, false)) {
            ;
        }
        {
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("msg_obj_modified"), true);
            $this->returnToParent();
        }
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    protected function getRootCourseId() {
        $current_ref_id = $_GET['ref_id'];

        $root_course = false;
        for ($ref_id = $current_ref_id; $ref_id; $ref_id = $this->tree->getParentNodeData($ref_id)['ref_id'] ) {
            $node_data = $this->tree->getNodeData($ref_id);
            if (empty($node_data) || $node_data["type"] == "crs") {
                $root_course = $node_data;
                break;
            }
        }

        return $root_course['ref_id'];
    }

    /**
     * Init editing form
     */
    protected function initForm(bool $a_create = false) : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();

        $select_options = [];
        foreach($this->tree->getSubTree($this->tree->getNodeData($this->getRootCourseId())) as $obj) {
            $select_options[$obj["ref_id"]] = $obj['title'] . " (" . $obj['type'] . " / " . $obj['last_update'] . ")";
        }

        // choose object
        $input_ref_if = new ilSelectInputGUI($this->lng->txt("object"), "ref_id");
        $input_ref_if->setRequired(true);
        $input_ref_if->setOptions($select_options);
        $form->addItem($input_ref_if);
        
        // title
        $input_title = new ilTextInputGUI($this->lng->txt("title"), 'title');
        $input_title->setMaxLength(255);
        $input_title->setSize(40);
        $input_title->setRequired(false);
        $form->addItem($input_title);
        
        // description
        $input_description = new ilTextInputGUI($this->lng->txt("description"), 'description');
        $input_description->setMaxLength(255);
        $input_description->setSize(40);
        $input_description->setRequired(false);
        $form->addItem($input_description);
        
        // save and cancel commands
        if ($a_create) {
            $this->addCreationButton($form);
            $form->addCommandButton("cancel", $this->lng->txt("cancel"));
            $form->setTitle($this->plugin->getPluginName());
        } else {
            $prop = $this->getProperties();
            $input_ref_if->setValue($prop['ref_id']);
            $input_title->setValue($prop['title']);
            $input_description->setValue($prop['description']);

            $form->addCommandButton("update", $this->lng->txt("save"));
            $form->addCommandButton("cancel", $this->lng->txt("cancel"));
            $form->setTitle($this->plugin->getPluginName());
        }

        $form->setFormAction($this->ctrl->getFormAction($this));
        return $form;
    }

    protected function saveForm(ilPropertyFormGUI $form, bool $a_create) : bool
    {
        if ($form->checkInput()) {
            $properties = $this->getProperties();

            $properties['ref_id'] = $form->getInput('ref_id');
            $properties['title'] = $form->getInput('title');
            $properties['description'] = $form->getInput('description');

            if ($a_create) {
                return $this->createElement($properties);
            } else {
                return $this->updateElement($properties);
            }
        }

        return false;
    }

    /**
     * Cancel
     */
    public function cancel()
    {
        $this->returnToParent();
    }

    /**
     * Get HTML for element
     * @param string    page mode (edit, presentation, print, preview, offline)
     * @return string   html code
     */
    public function getElementHTML(/* string */ $a_mode, /* array */ $a_properties, /* string */ $a_plugin_version) /* : string */
    {
        $ref_id = $a_properties['ref_id'];
        $obj = ilObjectFactory::getInstanceByRefId($ref_id);
        $obj_id = $obj->getId();
        $type = $obj->getType();
        $title = !empty($a_properties['title']) ? $a_properties['title'] : $obj->getTitle();
        $description = !empty($a_properties['description']) ? $a_properties['description'] : $obj->getDescription();
        $tile_image = $this->object->commonSettings()->tileImage()->getByObjId($obj_id);
        
        /* TODO: find a proper way to get the permalink -> maybe with $this->ctrl->getLinkTarget */
        $permalink = "";
        if ($type == "lm") $permalink = "/ilias.php?baseClass=ilLMPresentationGUI&ref_id=" . $ref_id . "&cmd=resume";
        elseif ($type == "file") $permalink = "/goto.php?target=file_" . $ref_id . "_download";
        elseif ($type == "sahs") $permalink = "/ilias.php?baseClass=ilSAHSPresentationGUI&ref_id=" . $ref_id . "";
        elseif ($type == "htlm") $permalink = "/ilias.php?baseClass=ilHTLMPresentationGUI&ref_id=" . $ref_id . "";
        elseif ($type == "tst") $permalink = "/goto.php?target=tst_" . $ref_id . "&client_id=default";
        elseif ($type == "fold") $permalink = "/ilias.php?ref_id=" . $ref_id . "&cmd=view&cmdClass=ilrepositorygui&cmdNode=wj&baseClass=ilrepositorygui";
        //elseif ($type == "tst") $permalink = "/ilias.php?ref_id=" . $ref_id . "&sequence=1&active_id=3&cmd=showQuestion&cmdClass=iltestplayerfixedquestionsetgui&cmdNode=wn:r5:13x&baseClass=ilrepositorygui";

        /* progress statuses:
        0 = attempt
        1 = in progress;
        2 = completed;
        3 = failed;
         */
        $lp = ilLearningProgress::_getProgress($this->user->getId(), $obj_id);
        $lp_status = ilLPStatus::_lookupStatus($obj_id, $this->user->getId());
        $lp_percent = ilLPStatus::_lookupPercentage($obj_id, $this->user->getId());
        $lp_in_progress = !empty(ilLPStatus::_lookupInProgressForObject($obj_id, [$this->user->getId()]));
        $lp_completed = ilLPStatus::_hasUserCompleted($obj_id, $this->user->getId());
        $lp_failed = !empty(ilLPStatus::_lookupFailedForObject($obj_id, [$this->user->getId()]));
        $lp_downloaded = $lp['visits'] > 0 && $type == "file";

        ob_start();
        ?>
        <div class="kalamun-card" data-type="<?= $type; ?>" data-id="<?= $ref_id; ?>">
            <?php if ($this->ctrl->getCmd() == "edit") {
                ?><div class="kalamun-card_prevent-link"></div><?php
            } ?>
            <div class="kalamun-card_inner">
                <div class="kalamun-card_image">
                    <?= ($tile_image->exists() ? '<a href="' . $permalink . '" title="' . addslashes($title) . '"><img src="' . $tile_image->getFullPath() . '"></a>' : ''); ?>
                    <?php
                    if ($type == "file") {
                        ?>
                        <a href="<?= $permalink; ?>" title="<?= addslashes($title); ?>">
                            <svg width="26.277" height="29.83" viewBox="0 0 26.277 29.83">
                                <path d="M33.777,15.028H26.27V4.5H15.008V15.028H7.5L20.639,27.311ZM7.5,30.821V34.33H33.777V30.821Z" transform="translate(-7.5 -4.5)" fill="#006cbe"/>
                            </svg>
                        </a>
                        <?php
                    }
                    elseif ($type == "tst") {
                        ?>
                        <a href="<?= $permalink; ?>" title="<?= addslashes($title); ?>">
                            <svg height="30px" viewBox="0 0 24 24" width="30px">
                                <path d="M19.94,9.06C19.5,5.73,16.57,3,13,3C9.47,3,6.57,5.61,6.08,9l-1.93,3.48C3.74,13.14,4.22,14,5,14h1l0,2c0,1.1,0.9,2,2,2h1 v3h7l0-4.68C18.62,15.07,20.35,12.24,19.94,9.06z M12.5,14c-0.41,0-0.74-0.33-0.74-0.74c0-0.41,0.33-0.73,0.74-0.73 c0.41,0,0.73,0.32,0.73,0.73C13.23,13.67,12.92,14,12.5,14z M14.26,9.68c-0.44,0.65-0.86,0.85-1.09,1.27 c-0.09,0.17-0.13,0.28-0.13,0.82h-1.06c0-0.29-0.04-0.75,0.18-1.16c0.28-0.51,0.83-0.81,1.14-1.26c0.33-0.47,0.15-1.36-0.8-1.36 c-0.62,0-0.92,0.47-1.05,0.86l-0.96-0.4C10.76,7.67,11.46,7,12.5,7c0.86,0,1.45,0.39,1.75,0.88C14.51,8.31,14.66,9.1,14.26,9.68z" fill="#006cbe"/>
                            </svg>
                        </a>
                        <?php
                    }
                    ?>
                </div>
                <div class="kalamun-card_body">
                    <div class="kalamun-card_title">
                        <a href="<?= $permalink; ?>" title="<?= addslashes($title); ?>"><?= $title; ?></a>
                    </div>
                    <?php
                    if (!empty($description)) { ?>
                        <div class="kalamun-card_description"><?= $description; ?></div>
                    <?php }
                    ?>
                    <?php
                    if (!empty($lp_downloaded)) { ?>
                        <div class="kalamun-card_progress downloaded completed"><?= $this->plugin->txt('downloaded'); ?></div>
                    <?php }
                    elseif (!empty($lp_completed)) { ?>
                        <div class="kalamun-card_progress completed"><?= $this->plugin->txt('completed'); ?></div>
                    <?php }
                    elseif (!empty($lp_in_progress)) { ?>
                        <div class="kalamun-card_progress in-progress"><?= $this->plugin->txt('in_progress'); ?></div>
                    <?php }
                    elseif (!empty($lp_failed)) { ?>
                        <div class="kalamun-card_progress failed"><?= $this->plugin->txt('failed'); ?></div>
                    <?php }
                    else { ?>
                        <div class="kalamun-card_progress not-started"></div>
                    <?php }
                    ?>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        return $html;
    }
}