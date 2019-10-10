<?php

	require('../config.php');
	
	if(empty($_POST['TMoveLine'])) exit;
	
	$TMoveLine = GETPOST('TMoveLine');
	$element=GETPOST('element');
	$action = GETPOST('action');
	
	
	$object = new $element($db);
	$object->fetch(GETPOST('id'));
	
	$old_object = new $element($db);
	$old_object->fetch(GETPOST('id'));
	
	$entity = GETPOST('split_entity');
	if(empty($entity))$entity=$conf->entity;
	
	if($action == 'split' || $action=='copy') {
		
		$fk_target = GETPOST('fk_propal_split');
		if ($fk_target > 0)
		{
			$new_object = new $element($db);
			$new_object->fetch($fk_target);
			
			foreach ($TMoveLine as $k => $line)
			{
				$line = $old_object->lines[$k];
				/**
				 * @var Propal pour le moment le split ce fait que sur une propal
				 */
				$newLineId = $new_object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, 'HT', 0, 0, $line->product_type, -1, $line->special_code, 0, 0, $line->pa_ht, $line->label, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, '', 0, 0, $line->fk_remise_except);

				if($conf->nomenclature->enabled && in_array($element, array('propal', 'commande'))) {
				    dol_include_once('/nomenclature/class/nomenclature.class.php');
				    $PDOdb = new TPDOdb;
                    $n = new TNomenclature;
                    $n->loadByObjectId($PDOdb, $line->id, $element, true, $line->fk_product, $line->qty, $old_object->id);

                    $coef = new TNomenclatureCoefObject;
                    $TCoef = $coef->loadCoefObject($PDOdb, $new_object, $element, $new_object->id);
                    var_dump($TCoef);
                    //$n->setPrice($PDOdb, $line->qty, null, $element, $old_object->id);
                    if($n->rowid == 0 && (count($n->TNomenclatureDet) + count($n->TNomenclatureWorkstation)) > 0) {
                        // Le cas d'une nomenclature non chargée : ça ne sert à rien de copier la Nomenclature...
                        continue;
                    }

                    $newN = new TNomenclature;
                    $newN->loadByObjectId($PDOdb, $line->id, $element, true, $line->fk_product, $line->qty, $old_object->id);
					$newN->reinit();
					$newN->object_type = $element;
					$newN->fk_object = $newLineId;
					//$newN->setPrice($PDOdb, $line->qty, null, $element, $new_object->id);
					var_dump($n);
					$newN->save($PDOdb);
					/*var_dump($newN->rowid == 0 && (count($newN->TNomenclatureDet) + count($newN->TNomenclatureWorkstation)) > 0, $newN->iExist);
					if($newN->rowid == 0 && (count($newN->TNomenclatureDet) + count($newN->TNomenclatureWorkstation)) > 0) {
						// Charger une nomenclature en local ça peut aider des fois !
						if(!$newN->iExist) $newN->reinit();
						$newN->object_type = $element;
						$newN->fk_object = $newLineId;

						$newN->setPrice($PDOdb, $line->qty, null, $element, $new_object->id);
						$newN->save($PDOdb);
					}

					var_dump($n->TNomenclatureDet);
					var_dump($newN->TNomenclatureDet);
					$n->fetchCombinedDetails($PDOdb);
					/*$newN->fetchCombinedDetails($PDOdb);
					foreach($n->TNomenclatureDetCombined as $fk_product => $det) {
						foreach($det as $attr => alue) {
							if(in_array($attr, array('rowid', 'id', 'date_cre', 'date_maj'))) continue;

							$newN->TNomenclatureDetCombined[$fk_product]->$attr = $value;
						}

						$newN->TNomenclatureDetCombined[$fk_product]->save($PDOdb);
					}

					$newN->save($PDOdb);
					$newN->setPrice($PDOdb, $line->qty, null, $element, $new_object->id);*/
                }
			}
		}
		else
		{
		    /** @var Propal $object */
		    if ((float) DOL_VERSION >= 10.0) $id_new = $object->createFromClone($user, (int)GETPOST('socid'));
			else $id_new = $object->createFromClone((int)GETPOST('socid'));
		//	print "création $id_new<br>";
			$new_object = new $element($db);
			$new_object->fetch($id_new);
		//	var_dump($TMoveLine,$new_object->lines);
			
			foreach($new_object->lines as $k=>$line) {

				$lineid = empty($line->id) ? $line->rowid : $line->id;

				if(!isset($TMoveLine[$k])) {
		 //       	print "Suppresion ligne $k $lineid<br>";
						$new_object->deleteline($lineid, $user);
				}
				else{
		   //	 	print "ok $k $lineid<br>";
				}
			}
		}  		
		
		if($entity!=$conf->entity) {
			
			$db->query("UPDATE ".MAIN_DB_PREFIX.$new_object->table_element." SET entity=".$entity." WHERE rowid=".$new_object->id );
			
		}
		
	}
	
	
	if($action == 'split' || $action == 'delete' ) {	
		foreach($old_object->lines as $k=>$line) {
	                 
	         $lineid = empty($line->id) ? $line->rowid : $line->id;
	         
	         if(isset($TMoveLine[$k])) {
	         	print "Suppresion ligne old $lineid";
	                 $old_object->deleteline($lineid, $user);
	         }
	    }       
	}
