<?php echo form_open(uri_string(), 'class="crud"'); ?>

	<section class="title">
		<h4><?php echo lang('firesale:shortcuts:assign_taxes'); ?></h4>
	</section>

	<section class="item form_inputs">

			<div class="form_inputs">
				<table>
					<thead>
						<tr>
							<td style="width:100px">&nbsp;</td>
							<?php foreach ($taxes as $tax): ?>
								<td><?php echo $tax['title']; ?></td>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($currencies as $currency): ?>
							<tr>
								<td><strong><?php echo $currency['title']; ?></strong></td>
								<?php foreach ($currency['taxes'] as $tax): ?>
									<td><?php echo form_input($currency['id'] . '[' . $tax['id'] . ']', $tax['value']); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

		<div class="buttons">
			<?php $this->load->view('admin/partials/buttons', array('buttons' => array('save', 'save_exit', 'cancel') )); ?>
		</div>

	</section>
<?php echo form_close(); ?>