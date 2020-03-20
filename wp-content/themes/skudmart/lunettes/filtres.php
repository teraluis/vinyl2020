                    <button class="filters__close">recherche avancée</button>                    
                    <form class="filters__form" id="filters" action="" method="GET">
                        <div class="filters__form__group">
                            <div class="filters__form__heading">Recherche par nom</div>
                            <input type="text" id="searchcat" class="form-control" placeholder="Nom de la monture">
                        </div>
                        <div class="filters__form__group">
                            <div class="filters__form__heading">usage</div>
                            <input type="checkbox" id="optical" name="sous_famille[]" value="Optique" class="styled" >
                            <label for="optical"><span></span>Optique</label>
                            <input type="checkbox" id="solar" name="sous_famille[]" value="Solaire" class="styled" >
                            <label class="filters__form__label-solar" for="solar"><span></span>Solaire</label>
                        </div>

                        <div class="filters__form__group forms">
                            <div class="filters__form__heading">forme</div>
                            <div class="montures-list__form-item">
                                <input type="checkbox" id="pantos" name="forme[]" value="PANTOS" class="styled" >
                                <label class="pantos" for="pantos"></label><span>pantos</span>
                            </div>
                            <div class="montures-list__form-item">
                                <input type="checkbox" id="pilote" name="forme[]" value="PILOTE" class="styled" >
                                <label class="pilote" for="pilote"></label><span>pilote</span>
                            </div>
                            <div class="montures-list__form-item">
                                <input type="checkbox" id="rectangle" name="forme[]" value="RECTANGULAIRE" class="styled" >
                                <label class="rectangle" for="rectangle"></label><span> rectangle </span>
                            </div>
                            <div class="montures-list__form-item">
                                <input type="checkbox" id="rondes" name="forme[]" value="RONDE" class="styled" >
                                <label class="rondes" for="rondes"></label><span>rondes</span>
                            </div>
                            <div class="montures-list__form-item">
                                <input type="checkbox" id="ovales" name="forme[]" value="OVAL" class="styled" >
                                <label class="ovales" for="ovales"></label><span>ovales</span>
                            </div>
                            <div class="montures-list__form-item">
                                <input type="checkbox" id="papilon" name="forme[]" value="PAPILLONNANTE" class="styled" >
                                <label class="papillon" for="papilon"></label><span><?php echo $content_traduction["papillon"]; ?></span>
                            </div>
                        </div>

                        <div class="filters__form__group colors">
                            <div class="filters__form__heading">couleur</div>
                            <div class="colors__row">
                                <div class="montures-list__color-item">
                                    <input id="color1" type="checkbox" name="couleur[]" value="NOIR" class="styled" >
                                    <label for="color1" style="background-color: #171717;"></label><span>Noir</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color2" type="checkbox" name="couleur[]" value="MARRON" class="styled" >
                                    <label for="color2" style="background-color: #844d34;"></label><span>Marron</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color3" type="checkbox" name="couleur[]" value="GOLD" class="styled" >
                                    <label for="color3" style="background-color: #dabc6b;"></label><span>Or</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color4" type="checkbox" name="couleur[]" value="SILVER" class="styled" >
                                    <label for="color4" style="background-color: #b9b5ab;"></label><span>Argent</span>
                                </div>
                                <div class="montures-list__color-item montures-list__color-item--inverse">
                                    <input id="color5" type="checkbox" name="couleur[]" value="CRYSTAL" class="styled" >
                                    <label for="color5" style="background-color: #e5e8ec;"></label><span>Crystal</span>
                                </div>
                            </div>
                            <div class="colors__row">
                                <div class="montures-list__color-item montures-list__color-item--inverse">
                                    <input id="color6" type="checkbox" name="couleur[]" value="BLANC" class="styled" >
                                    <label for="color6" style="background-color: #ffffff;"></label><span>Blanc</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color7" type="checkbox" name="couleur[]" value="ROUGE" class="styled" >
                                    <label for="color7" style="background-color: #ff0000;"></label><span>Rouge</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color8" type="checkbox" name="couleur[]" value="ORANGE" class="styled" >
                                    <label for="color8" style="background-color: #fc8903;"></label><span>Orange</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color9" type="checkbox" name="couleur[]" value="JAUNE" class="styled" >
                                    <label for="color9" style="background-color: #fde22f;"></label><span>Jaune</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color10" type="checkbox" name="couleur[]" value="ROSE" class="styled" >
                                    <label for="color10" style="background-color: #e55783;"></label><span>Rose</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color11" type="checkbox" name="couleur[]" value="GRIS" class="styled" >
                                    <label for="color11" style="background-color: #999999;"></label><span>Gris</span>
                                </div>
                            </div>
                            <div class="colors__row">
                                <div class="montures-list__color-item">
                                    <input id="color12" type="checkbox" name="couleur[]" value="BLEU" class="styled" >
                                    <label for="color12" style="background-color: #005aff;"></label><span>Bleu</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color13" type="checkbox" name="couleur[]" value="VIOLET" class="styled" >
                                    <label for="color13" style="background-color: #7e33b4;"></label><span>Violet</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color14" type="checkbox" name="couleur[]" value="VERT" class="styled" >
                                    <label for="color14" style="background-color: #35b973;"></label>
                                    <span>Vert</span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color15" type="checkbox" name="couleur[]" value="CREME" class="styled" >
                                    <label for="color15" style="background-color: #ffe7c8;"></label>
                                    <span>
                                        Crème
                                    </span>
                                </div>
                                <div class="montures-list__color-item">
                                    <input id="color16" type="checkbox" name="couleur[]" value="OR ROSE" class="styled" >
                                    <label for="color16" style="background-color: #ffaf98;"></label>
                                    <span>Or rose</span>
                                </div>
                            </div>
                        </div>

                        <div class="filters__form__group materials">
                            <div class="filters__form__heading">Matière</div>
                            <input type="checkbox" id="metal" name="matiere[]" value="ME" class="styled" >
                            <label for="metal"><span></span>Métal</label><br>
                            <input type="checkbox" id="composite" name="matiere[]" value="CO" class="styled" >
                            <label for="composite"><span></span>Composite</label><br>
                            <input type="checkbox" id="acetate" name="matiere[]" value="AT" class="styled" >
                            <label for="acetate"><span></span>Acétate</label><                            
                        </div>
                        <input type="hidden" name="page" value="<?php ?>">
                    </form>
                    <script>
                        jQuery(".filters__close").click( function(){
                           jQuery("#filters").toggle();
                        });
                    </script>