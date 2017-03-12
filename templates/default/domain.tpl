<H1>Domain :: {{ domain.domain }}</H1>

<form method="post" id="editsoaform">
<input type="hidden" name="changetype" value="soa">
<table id="soainfo" class="table table-striped table-bordered">
	<tbody>
		<tr>
			<th>Primary Nameserver</th>
			<td data-name="primaryNS" data-value="{{ domain.SOA.primaryNS }}">{{ domain.SOA.primaryNS }}</td>
		</tr>
		<tr>
			<th>Admin Email Address</th>
			<td data-name="adminAddress" data-value="{{ domain.SOA.adminAddress }}">{{ domain.SOA.adminAddress }}</td>
		</tr>
		<tr>
			<th>Serial Number</th>
			<td data-name="serial" data-value="{{ domain.SOA.serial }}">{{ domain.SOA.serial }}</td>
		</tr>
		<tr>
			<th>Refresh Time</th>
			<td data-name="refresh" data-value="{{ domain.SOA.refresh }}">{{ domain.SOA.refresh }}</td>
		</tr>
		<tr>
			<th>Retry Time</th>
			<td data-name="retry" data-value="{{ domain.SOA.retry }}">{{ domain.SOA.retry }}</td>
		</tr>
		<tr>
			<th>Expire Time</th>
			<td data-name="expire" data-value="{{ domain.SOA.expire }}">{{ domain.SOA.expire }}</td>
		</tr>
		<tr>
			<th>Negative TTL</th>
			<td data-name="minttl" data-value="{{ domain.SOA.minttl }}">{{ domain.SOA.minttl }}</td>
		</tr>
		<tr>
			<th>Disabled</th>
			<td data-radio="disabled" data-value="{{ domain.disabled | yesno }}">
			{% if domain.disabled == 'true' %}
				Yes
			{% else %}
				No
			{% endif %}
			</td>
		</tr>
		<tr>
			<th>Access level</th>
			<td data-myaccess="{{ domain.access }}">{{ domain.access | capitalize }}</td>
		</tr>
	</tbody>
</table>
</form>

<div class="row" id="domaincontrols">
	<div class="col">
		<a href="{{ url("/domain/#{domain.domain}/records") }}" class="btn btn-primary" role="button">View/Edit Records</a>

		{% if domain.access == 'owner' or domain.access == 'admin' or domain.access == 'write' %}
			<button type="button" data-action="editsoa" class="btn btn-primary" role="button">Edit SOA</button>
			<button type="button" data-action="savesoa" class="btn btn-success hidden" role="button">Save</button>
		{% endif %}


		{% if domain.access == 'owner' %}
			<div class="float-right">
				<button type="button" class="btn btn-danger" role="button" data-toggle="modal" data-target="#deleteModal" data-backdrop="static">Delete Domain</button>
			</div>

			<!-- Modal -->
			<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="deleteModalLabel">Delete Domain</h5>
						</div>
						<div class="modal-body">
							Are you sure you want to delete this domain?
							<br><br>
							This will delete all records and data associated with this domain and can not be undone.
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
							<form id="deletedomainform" method="post" action="{{ url("/domain/#{domain.domain}/delete") }}">
								<input type="hidden" name="confirm" value="true">
								<button type="submit" class="btn btn-danger">Delete domain</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		{% endif %}
	</div>
</div>

<br><br>

<H2>Domain Access</H2>

<form method="post">
<input type="hidden" name="changetype" value="access">
<table id="accessinfo" class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Who</th>
			<th>Access Level</th>
			{% if domain.access == 'owner' or domain.access == 'admin' %}
				<th>Actions</th>
			{% endif %}
		</tr>
	</thead>
	<tbody>
		{% for email,access in domainaccess %}
		<tr {% if editedaccess[email] %} data-edited="true"{% endif %}>
			<td class="who" data-value="{{ email }}">
				<img src="{{ email | gravatar }}" alt="{{ email }}" class="minigravatar" />&nbsp;
				{{ email }}
			</td>
			<td class="access" data-value="{{ access }}" {% if editedaccess[email] %} data-edited-value="{{ editedaccess[email].level }}" {% endif %}>
				{{ access }}
			</td>
			{% if domain.access == 'owner' or domain.access == 'admin' %}
				<td>
					{% if email != user.email %}
						<button type="button" data-action="editaccess" class="btn btn-sm btn-success" role="button">Edit</button>
					{% endif %}
				</td>
			{% endif %}
		</tr>
		{% endfor %}

		{% for new in newaccess %}
		<tr class="new form-group" data-edited="true">
			<td class="who" data-edited-value="{{ new.who }}"></td>
			<td class="access" data-edited-value="{{ new.level }}"></td>
			<td>
				<button type="button" class="btn btn-sm btn-danger" data-action="editaccess" role="button">Edit</button>
				<button type="button" class="btn btn-sm btn-danger" data-action="deleteaccess" role="button">Cancel</button>
			</td>
		</tr>
		{% endfor %}
	</tbody>
</table>

{% if domain.access == 'owner' or domain.access == 'admin' %}
	<button type="button" data-action="addaccess" class="btn btn-success" role="button">Add Access</button>
	<button type="submit" class="btn btn-primary" role="button">Update Access</button>
{% endif %}
</form>


<script src="{{ url('/assets/domains.js') }}"></script>
