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
                if (in_array($cmd, array("create", "save", "edit", "update", "cancel", "downloadFile"))) {
                    $this->$cmd();
                }
                break;
        }
    }

    private static function get_inline_js() : string
    {
        $root_course = dciSkin_tabs::getRootCourse($_GET['ref_id']);
        $mandatory_objects = dciCourse::get_mandatory_objects($root_course['obj_id']);
        
        ob_start();
        ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                const fieldObject = document.querySelector('select[name=ref_id]');
                const fieldMandatory = document.querySelector('input[name=mandatory]');
    
                const updateMandatory = () => {
                    const mandatoryObjects = <?= json_encode($mandatory_objects); ?>;
                    
                    if (fieldObject && fieldMandatory && Array.isArray(mandatoryObjects)) {
                        fieldMandatory.checked = mandatoryObjects.some(obj => obj.ref_id === fieldObject.value);
                    }
                }
    
                updateMandatory();
                fieldObject?.addEventListener('change', updateMandatory);
            });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Create
     */
    public function insert() : void
    {
        $form = $this->initForm(true);

        $html = $form->getHTML();
        $html .= self::get_inline_js();

        $this->tpl->setContent($html);
    }

    /**
     * Save new pc example element
     */
    public function create() : void
    {
        $form = $this->initForm(true);
        if ($this->saveForm($form, true)) {
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("msg_obj_modified"), true);
            $this->returnToParent();
        }
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    public function edit() : void
    {
        $form = $this->initForm();

        $html = $form->getHTML();
        $html .= self::get_inline_js();

        $this->tpl->setContent($html);
    }

    public function update() : void
    {
        $form = $this->initForm(false);
        if ($this->saveForm($form, false)) {
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("msg_obj_modified"), true);
            $this->returnToParent();
        }
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Init editing form
     */
    protected function initForm(bool $a_create = false) : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();

        $root_course = dciSkin_tabs::getRootCourse($_GET['ref_id']);
        $subTree = $this->tree->getSubTree($this->tree->getNodeData($root_course['ref_id']));
        
        $select_options = [];
        foreach($subTree as $obj) {
            $select_options[$obj["ref_id"]] = $obj['title'] . " (" . $obj['type'] . " / " . $obj['last_update'] . ")";
        }
        natcasesort($select_options);

        // choose object
        $input_ref_if = new ilSelectInputGUI($this->lng->txt("object"), "ref_id");
        $input_ref_if->setRequired(true);
        $input_ref_if->setOptions($select_options);
        $form->addItem($input_ref_if);

        // thumbnail
        $thumbnail = new ilImageFileInputGUI($this->lng->txt("thumbnail"), 'thumbnail');
        $thumbnail->setAllowDeletion(true);
        $thumbnail->setRequired(false);

        $form->addItem($thumbnail);
        
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

        // dates
        $starting_date = new ilDateTimeInputGUI($this->lng->txt("starting_date"), 'starting_date');
        $starting_date->setShowTime(true);
        $starting_date->setRequired(false);
        $form->addItem($starting_date);

        $duration = new ilDurationInputGUI($this->lng->txt("duration"), 'duration');
        $duration->setRequired(false);
        $form->addItem($duration);

        // layout
        $select_layout = new ilSelectInputGUI($this->plugin->txt("layout"));
        $select_layout->setPostVar("layout");
        $select_layout->setOptions(["square" => $this->plugin->txt("square"), "wide" => $this->plugin->txt("wide")]);
        $select_layout->setRequired(true);
        $form->addItem($select_layout);

        // mandatory
        $input_mandatory = new ilCheckBoxInputGUI($this->lng->txt("mandatory", 'mandatory'));
        $input_mandatory->setRequired(false);
        $form->addItem($input_mandatory);
        
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
            $starting_date->setDate(new ilDateTime($prop['starting_date'], IL_CAL_DATETIME));
            $duration->setHours(explode(":", $prop['duration'])[0]);
            $duration->setMinutes(explode(":", $prop['duration'])[1]);
            $select_layout->setValue($prop['layout']);

            if (!emptY($prop['thumbnail'])) {
                $fileObj = new ilObjFile($prop['thumbnail'], false);
                if (!empty($fileObj)) {
                    $_SESSION[__CLASS__]['allowedFiles'][$fileObj->getId()] = true;
                    $this->ctrl->setParameter($this, 'id', $fileObj->getId());
                    $image_url = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCardPluginGUI'), 'downloadFile');
                    $thumbnail->setImage($image_url);
                }
            }

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
            $properties['layout'] = $form->getInput('layout');
            $properties['starting_date'] = $form->getInput('starting_date');
            $properties['duration'] = implode(":", $form->getInput('duration'));

            $mandatory = $form->getInput('mandatory');
            $root_course = dciSkin_tabs::getRootCourse($_GET['ref_id']);
            dciCourse::update_mandatory_object($root_course['obj_id'], $properties['ref_id'], $mandatory);

            foreach(["thumbnail"] as $key) {
                if (!empty($_FILES[$key]["name"])) {
                    $old_file_id = empty($properties[$key]) ? null : $properties[$key];
                    
                    $fileObj = new ilObjFile((int) $old_file_id, false);
                    $fileObj->setType("file");
                    $fileObj->setTitle($_FILES[$key]["name"]);
                    $fileObj->setDescription("");
                    $fileObj->setFileName($_FILES[$key]["name"]);
                    $fileObj->setMode("filelist");
                    if (empty($old_file_id)) {
                        $fileObj->create();
                    } else {
                        $fileObj->update();
                    }
    
                    // upload file to filesystem
                    if ($_FILES[$key]["tmp_name"] !== "") {
                        $fileObj->getUploadFile(
                            $_FILES[$key]["tmp_name"],
                            $_FILES[$key]["name"]
                        );
                    }
    
                    $properties[$key] = $fileObj->getId();
                }
            }

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
        $lp_mode = 0; //$obj->getLPMode();
        $type = $obj->getType();
        $title = !empty($a_properties['title']) ? $a_properties['title'] : $obj->getTitle();
        $description = !empty($a_properties['description']) ? $a_properties['description'] : $obj->getDescription();
        $layout = !empty($a_properties['layout']) ? $a_properties['layout'] : "square";
        $starting_date = !empty($a_properties['starting_date']) ? $a_properties['starting_date'] : false;
        $duration = !empty($a_properties['duration']) ? explode(":", $a_properties['duration']) : false;

        $starting_date_timestamp = false;
        $ending_date_timestamp = false;
        if (!empty($starting_date)) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $starting_date);
            $starting_date_timestamp = $date->getTimestamp();

            if (!empty($duration) && !empty($starting_date_timestamp)) {
                $ending_date_timestamp = $starting_date_timestamp + ($duration[0] * 60 * 60) + ($duration[1] * 60);
            }
        }

        // thumbnail
        $thumbnail_url = "";
        if (!emptY($a_properties['thumbnail'])) {
            $fileObj = new ilObjFile($a_properties['thumbnail'], false);
            if (!empty($fileObj)) {
                $_SESSION[__CLASS__]['allowedFiles'][$fileObj->getId()] = true;
                $this->ctrl->setParameter($this, 'id', $fileObj->getId());
                $thumbnail_url = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilCardPluginGUI'), 'downloadFile');
            }
        } else {
            $tile_image = $this->object->commonSettings()->tileImage()->getByObjId($obj_id);
            $thumbnail_url = $tile_image->exists() ? $tile_image->getFullPath() : "";
        }


        // learning progress
        $typical_learning_time = ilMDEducational::_getTypicalLearningTimeSeconds($obj_id);

        $permalink = "";
        if ($type == "lm") {
            $this->ctrl->setParameterByClass("ilLMPresentationGUI", "ref_id", $ref_id);
            $permalink = $this->ctrl->getLinkTargetByClass("ilLMPresentationGUI", "resume");
            //$permalink = "/ilias.php?baseClass=ilLMPresentationGUI&ref_id=" . $ref_id . "&cmd=resume";
        } elseif ($type == "file") {
            $permalink = "/goto.php?target=file_" . $ref_id . "_download";
        } elseif ($type == "sahs") {
            $this->ctrl->setParameterByClass("ilSAHSPresentationGUI", "ref_id", $ref_id);
            $permalink = $this->ctrl->getLinkTargetByClass("ilSAHSPresentationGUI", "");
            //$permalink = "/ilias.php?baseClass=ilSAHSPresentationGUI&ref_id=" . $ref_id . "";
        } elseif ($type == "htlm") {
            $this->ctrl->setParameterByClass("ilHTLMPresentationGUI", "ref_id", $ref_id);
            $permalink = $this->ctrl->getLinkTargetByClass("ilHTLMPresentationGUI", "view");
            //$permalink = "/ilias.php?baseClass=ilHTLMPresentationGUI&ref_id=" . $ref_id . "";
        } elseif ($type == "tst") {
            $this->ctrl->setParameterByClass("ilTestPresentationGUI", "ref_id", $ref_id);
            $permalink = $this->ctrl->getLinkTargetByClass("ilTestPresentationGUI", "view");
            //$permalink = "/goto.php?target=tst_" . $ref_id . "&client_id=default";
        } elseif ($type == "fold") {
            $this->ctrl->setParameterByClass("ilrepositorygui", "ref_id", $ref_id);
            $permalink = $this->ctrl->getLinkTargetByClass("ilrepositorygui", "view");
            //$permalink = "/ilias.php?ref_id=" . $ref_id . "&cmd=view&cmdClass=ilrepositorygui&cmdNode=wl&baseClass=ilrepositorygui";
        } elseif ($type == "xjit") {
            $this->ctrl->setParameterByClass("ilObjPluginDispatchGUI", "ref_id", $ref_id);
            $this->ctrl->setParameterByClass("ilObjPluginDispatchGUI", "forwardCmd", "showContents");
            $permalink = $this->ctrl->getLinkTargetByClass("ilObjPluginDispatchGUI", "forward");
            ///ilias.php?baseClass=ilObjPluginDispatchGUI&cmd=forward&ref_id=102&forwardCmd=showContents
        } else {
            $this->ctrl->setParameterByClass("ilrepositorygui", "ref_id", $ref_id);
            $permalink = $this->ctrl->getLinkTargetByClass("ilrepositorygui", "view");
            //$permalink = "/ilias.php?ref_id=87&cmdClass=ilrepositorygui&cmdNode=wm&baseClass=ilrepositorygui";
        }

        if (!empty($starting_date_timestamp) && time() < $starting_date_timestamp - (60 * 10)) {
            $permalink = "";
        } elseif (!empty($ending_date_timestamp) && time() > $ending_date_timestamp + (60 * 10)) {
            $permalink = "";
        }

        /* progress statuses:
        0 = attempt
        1 = in progress;
        2 = completed;
        3 = failed;
         */
        $supported_lp = ilObjectLP::isSupportedObjectType($type);

        if (!$supported_lp) {
            $lp = ['spent_seconds' => 0];
            $lp_status = 0;
            $lp_completed = false;
            $lp_in_progress = false;
            $lp_failed = false;
            $lp_downloaded = false;
            $lp_progresses = [];
            $lp_success_status = "unknown";
        } else {
            $lp = ilLearningProgress::_getProgress($this->user->getId(), $obj_id);
            $lp_status = ilLPStatus::_lookupStatus($obj_id, $this->user->getId());
            $lp_percent = $lp['spent_seconds'] < 60 ? 0 : ilLPStatus::_lookupPercentage($obj_id, $this->user->getId());
            $lp_in_progress = !empty(ilLPStatus::_lookupInProgressForObject($obj_id, [$this->user->getId()]));
            $lp_completed = ilLPStatus::_hasUserCompleted($obj_id, $this->user->getId());
            $lp_failed = !empty(ilLPStatus::_lookupFailedForObject($obj_id, [$this->user->getId()]));
            $lp_downloaded = $lp['visits'] > 0 && $type == "file";
            $has_tests = false;
            $lp_success_status = "unknown";
            $lp_completion_status = "unknown";
            $lp_scores = [];
            
            $lp_progresses = [];
            if (class_exists("dciCourse")) {
                $lp_progresses = dciCourse::get_obj_progress($obj_id, $this->user->getId());
                if (count($lp_progresses) > 0) {
                    $lp_passed = true;
                    foreach($lp_progresses as $progress) {
                        if (!empty($progress->c_max)) $has_tests = true;
                        if (!empty($progress->access_count)) $has_tests = true;
                        if ($progress->c_raw == false) continue;
                        $lp_scores[] = round($progress->c_raw);
                        $lp_success_status = $progress->success_status;
                        $lp_completion_status = $progress->completion_status;
                    }
                }
            }
        }
        
        $nice_spent_minutes = str_replace("00:", "", gmdate("H:i",($lp['spent_seconds']))) . " min";
        $nice_learning_time = str_replace("00:", "", gmdate("H:i", $typical_learning_time)) . " min";
        
        if( empty( $lp_percent ) && ($lp_completed || $lp_downloaded)) {
            $lp_percent = 100;
        }
        if( empty( $lp_percent ) && !empty($typical_learning_time)) {
            $lp_percent = round(90 / $typical_learning_time * $lp['spent_seconds']);
        }
        if( empty( $lp_percent ) && $lp_in_progress) {
            $lp_percent = 50;
        }
        if( $has_tests && $lp_completed) {
            $lp_percent = 100;
        }
        
        $has_progress = in_array($type, ["lm", "sahs", "file", "htlm", "tst"]);

        ob_start();
        ?>
        <div class="kalamun-card" data-layout="<?= $layout; ?>" data-type="<?= $type; ?>" data-id="<?= $ref_id; ?>">
            <?php if ($this->ctrl->getCmd() == "edit") {
                ?><div class="kalamun-card_prevent-link"></div><?php
            } ?>
            <div class="kalamun-card_inner">
                <div class="kalamun-card_image">
                    <?= $typical_learning_time ? '<div class="kalamun-card_learning-time"><span class="icon-clock"></span> ' . $nice_learning_time . '</div>' : ''; ?>
                    <?= (!$has_tests && ($lp_completed || $lp_downloaded)) ? '<div class="kalamun-card_status"><span class="icon-done"></span></div>' : ''; ?>
                    <?php
                    if ($has_tests && count($lp_scores) > 0) {
                        ?>
                        <div class="kalamun-card_status kalamun-card_scores result-<?= $lp_success_status; ?>">
                            <?php
                            if (count($lp_scores) > 0) {
                                foreach ($lp_scores as $score) {
                                    if ($score == "") continue;
                                    ?><span class="score"><?= $score; ?></span><?php
                                }
                            }
                            ?>
                            <span class="icon-<?= ($lp_success_status === "passed" ? 'done' : 'close'); ?>"></span>
                        </div>
                        <?php
                    }

                    if ($type !== "file" && $has_progress) {
                        ?><div class="kalamun-card_prgbar"><meter min="0" max="100" value="<?= $lp_percent; ?>"></meter></div><?php
                    } else {
                        ?><div class="kalamun-card_prgbar empty"></div><?php
                    }
                    ?>
                    <?= (!empty($permalink)) ? '<a href="' . $permalink . '" title="' . addslashes($title) . '">' : ''; ?>
                        <?= (!empty($thumbnail_url) ? '<img src="' . $thumbnail_url . '" class="kalamun-card_thumbnail" />' : '<span class="kalamun-card_thumbnail"></span>'); ?>
                    <?= (!empty($permalink)) ? '</a>' : ''; ?>
                </div>
                <div class="kalamun-card_body">
                    <div class="kalamun-card_title">
                        <?= (!empty($permalink)) ? '<a href="' . $permalink . '" title="' . addslashes($title) . '">' : ''; ?>
                            <?= $title; ?>
                        <?= (!empty($permalink)) ? '</a>' : ''; ?>
                    </div>
                    <?php
                    if (!empty($description)) { ?>
                        <div class="kalamun-card_description"><?= $description; ?></div>
                    <?php }
                    ?>
                    <?php
                    if (!empty($starting_date_timestamp)) { ?>
                        <div class="kalamun-card_timing">
                            <span class="kalamun-card_timing_date">
                                <span class="icon-calendar"></span>
                                <?= date("d F Y", $starting_date_timestamp); ?>
                            </span>
                            <span class="kalamun-card_timing_time">
                                <span class="icon-clock"></span>
                                <?= date("H:i", $starting_date_timestamp); ?>
                                <?php
                                if (!empty($ending_date_timestamp)) {
                                    echo ' <span class="icon-arrow-right"></span> ';
                                    echo date("H:i", $ending_date_timestamp);
                                }
                                ?>
                            </span>
                        </div>
                    <?php }
                    ?>
                    <div class="kalamun-card_cta">
                        <?= (!empty($permalink)) ? '<a href="' . $permalink . '" title="' . addslashes($title) . '">' : ''; ?>
                            <?php
                            if (empty($permalink)) { ?>
                                <div class="kalamun-card_noprogress"><button class="outlined"><?= $this->plugin->txt(time() < $ending_date_timestamp ? 'opens_10_minutes_before' : 'ended'); ?></span></button></div>
                            <?php }
                            elseif (!$type == "xjit") { ?>
                                <div class="kalamun-card_noprogress"><button><?= $this->plugin->txt('join_call'); ?> <span class="icon-right"></span></button></div>
                            <?php }
                            elseif (!$has_progress) { ?>
                                <div class="kalamun-card_noprogress"><button><?= $this->plugin->txt('open'); ?> <span class="icon-right"></span></button></div>
                            <?php }
                            elseif (!empty($lp_downloaded)) { ?>
                                <div class="kalamun-card_progress downloaded completed"><button class="outlined"><?= $this->plugin->txt('downloaded'); ?> <span class="icon-right"></span></button></div>
                            <?php }
                            elseif (!empty($lp_completed)) { ?>
                                <div class="kalamun-card_progress completed"><button class="outlined"><?= $this->plugin->txt('completed'); ?> <span class="icon-right"></span></button></div>
                            <?php }
                            elseif (!empty($lp_in_progress)) { ?>
                                <div class="kalamun-card_progress in-progress"><button><?= $this->plugin->txt('in_progress'); ?> <span class="icon-right"></span></button></div>
                            <?php }
                            elseif (!empty($lp_failed)) { ?>
                                <div class="kalamun-card_progress failed"><button><?= $this->plugin->txt('failed'); ?> <span class="icon-right"></span></button></div>
                            <?php }
                            else { ?>
                                <div class="kalamun-card_progress not-started"><button><?= $this->plugin->txt('start'); ?> <span class="icon-right"></span></button></div>
                            <?php }
                            ?>
                        <?= (!empty($permalink)) ? '</a>' : ''; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        return $html;
    }


    /**
     * download file of file lists
     */
    function downloadFile() : void
    {
        $file_id = (int) $_GET['id'];
        if ($_SESSION[__CLASS__]['allowedFiles'][$file_id]) {
            $fileObj = new ilObjFile($file_id, false);
            $fileObj->sendFile();
        } else {
            throw new ilException('not allowed');
        }
    }
}