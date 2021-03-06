{if is_array($debug.searches)}
	<h1 id="search_queries">{t}Searches{/t}</h1>
{foreach name=search from=$debug.searches item=value}
	<h2 class="queries query_read" id="search_{$smarty.foreach.search.index}"><a class="debug_toggle_view" rel="search_content_{$smarty.foreach.search.index}" href="#">{$smarty.foreach.search.index+1}. [R] {$value.tag}</a> <small>({$value.time|time_format} - match: {$value.total_found|default:''} elements - return: {$value.returned_rows|default:''} elements )</small></h2>
	<div id="search_content_{$smarty.foreach.search.index}" class="debug_contents">
		{foreach name=search_query from=$value.queries item=query}
		<h3 class="{if $query.error}query_error{/if}">{$smarty.foreach.search_query.index}. {$query.tag} <small>({$query.time|time_format} - match: {$query.total_found|default:''} elements - return: {$query.returned_rows|default:''} elements )</small></h3>
		{if $query.error}<p class="query_error"><b>{$query.error}</b></p>{/if}
		<table>
			<tr>
				<th>Query</th>
				<th>Filter</th>
				<th>Order</th>
				<th>GroupBy</th>
				<th>Indexs</th>
				<th>Trace</th>
			</tr>
			<tr>
				<td>{$query.query}</td>
				<td>
				{if isset($query.filters)}
					{foreach name=fil from=$query.filters item=filter}
					{$filter.attribute} = (
						{if is_array($filter.values)}
							{foreach name=val from=$filter.values item=value}
								{$value}{if !$smarty.foreach.val.last}, {/if}
							{/foreach}
						{else}
							{$filter.values}
						{/if}
						 ){if !$smarty.foreach.fil.last} && {/if}
					{/foreach}
				{/if}
				</td>
				<td>{if isset($query.sort.mode)}<em>{$query.sort.mode}</em>{/if} {if isset($query.sort.sortby)}- {$query.sort.sortby}{/if}</td>
				<td>{if isset($query.group.attribute)}{$query.group.attribute}{/if} {if isset($query.group.func)}<em>- Func: {$query.group.func}</em>{/if} {if isset($query.group.groupsort)}- Groupsort: {$query.group.groupsort}{/if}</td>
				<td>{$query.indexes}</td>
				<td>{$query.controller}</td>
			</tr>
		</table>
		<table>
			<tr>
				<th>WEIGHT</th>
				<th>ID</th>
{			foreach from=$query.resultset.attrs key=attribute item=values}
				<th>{$attribute}</th>
{			/foreach}
			</tr>
{if isset( $query.resultset.matches )}
{			foreach from=$query.resultset.matches key=id item=match}
			<tr>
				<td>{$match.weight}</td>
				<td>{$id}</td>
{				foreach from=$match.attrs key=attribute item=values}
				<td>{if is_array($values)}{$values|debug_print_var}{else}{$values}{/if}</td>
{				/foreach}
			</tr>
{			/foreach}
{/if}
		</table>
		{/foreach}
	</div>
{/foreach}
{/if}