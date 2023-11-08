<div class="bootstrap-yop wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h1>
        <?php _e( 'Add Ban', 'yop-poll' );?>
	</h1>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder addban">
			<!-- main content -->
			<div id="post-body-content ">
				<form>
					<input type="hidden" name="_token" id="_token" value="<?php echo wp_create_nonce( 'yop-poll-add-ban' );?>">
					<div class="yop-container">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <div class="yop-text">
                                        <label>
                                            <?php _e( 'Poll', 'yop-poll' );?>
                                        </label>
                                    </div>
                                    <select class="ban-poll admin-select" style="min-width:50%">
                                        <option value="0">
                                            <?php _e( 'All Polls', 'yop-poll' );?>
                                        </option>
                                        <?php
                                        foreach ( $polls as $poll ) {
                                            ?>
                                            <option value="<?php echo esc_attr( $poll->id )?>"><?php echo esc_html( $poll->name )?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
					</div>
					<div class="yop-container">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <div class="yop-text">
                                        <label>
                                            <?php _e( 'Ban By', 'yop-poll' );?>
                                        </label>
                                    </div>
                                    <select class="ban-by admin-select" style="min-width:50%">
                                        <option value="ip">
                                            <?php _e( 'IP', 'yop-poll' );?>
                                        </option>
                                        <option value="email">
                                            <?php _e( 'Email', 'yop-poll' );?>
                                        </option>
                                        <option value="username">
                                            <?php _e( 'Username', 'yop-poll' );?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
					</div>
					<div class="yop-container">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <div class="yop-text">
                                        <label>
                                            <?php _e( 'Value' , 'yop-poll' );?>
                                        </label>
                                    </div>
                                    <input type="text" class="form-control ban-value" style="width:50%">
                                </div>
                            </div>
                        </div>
					</div>
					<div class="yop-container">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <input
                                        name="addban"
                                        class="button button-primary button-large center add-ban"
                                        value="<?php _e( 'Add', 'yop-poll' );?>"
                                        type="submit">
                                </div>
                            </div>
                        </div>
					</div>
				</form>
			</div> <!-- #post-body  -->
			<br class="clear">
		</div> <!-- #poststuff -->
	</div> <!-- .wrap -->
</div>
