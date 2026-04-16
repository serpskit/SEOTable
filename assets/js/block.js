(function (wp) {
	'use strict';

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var registerBlock = wp.blocks.registerBlockType;
	var createBlock = wp.blocks.createBlock;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var BlockControls = wp.blockEditor.BlockControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var Button = wp.components.Button;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var ToolbarGroup = wp.components.ToolbarGroup;
	var ToolbarButton = wp.components.ToolbarButton;

	var BADGE_TYPES = [
		{label:'None',value:''},{label:'Green',value:'green'},{label:'Blue',value:'blue'},
		{label:'Amber',value:'amber'},{label:'Red',value:'red'},{label:'Gray',value:'gray'}
	];
	var ALIGN_OPTIONS = [{label:'Left',value:'left'},{label:'Center',value:'center'},{label:'Right',value:'right'}];

	function emptyCell() { return {text:'',bold:false,badge:null,brandTag:'',subtext:''}; }
	function cloneData(d) { return JSON.parse(JSON.stringify(d)); }

	function defaultData(cols, rows) {
		var h=[],a=[],cw=[];
		for(var c=0;c<cols;c++){h.push('Column '+(c+1));a.push('left');cw.push(0);}
		var r=[];
		for(var ri=0;ri<rows;ri++){var cells=[];for(var c2=0;c2<cols;c2++)cells.push(emptyCell());r.push({cells:cells});}
		return {headers:h,aligns:a,colWidths:cw,rows:r};
	}

	function parseCoreTable(attrs) {
		var data={headers:[],aligns:[],colWidths:[],rows:[]};
		var head=attrs.head||[], body=attrs.body||[];
		if(head.length>0&&head[0].cells){
			head[0].cells.forEach(function(c){data.headers.push(stripTags(c.content||''));data.aligns.push(c.align||'left');data.colWidths.push(0);});
		}
		body.forEach(function(row){
			if(!row.cells)return;
			var cells=[];
			row.cells.forEach(function(c){cells.push({text:stripTags(c.content||''),bold:false,badge:null,brandTag:'',subtext:''});});
			while(cells.length<data.headers.length)cells.push(emptyCell());
			data.rows.push({cells:cells});
		});
		if(data.headers.length===0&&data.rows.length>0){
			for(var i=0;i<data.rows[0].cells.length;i++){data.headers.push('Column '+(i+1));data.aligns.push('left');data.colWidths.push(0);}
		}
		return data;
	}

	function stripTags(html){var t=document.createElement('div');t.innerHTML=html;return t.textContent||t.innerText||'';}

	function SetupPanel(props) {
		var st1=useState(4),cols=st1[0],setCols=st1[1];
		var st2=useState(3),rows=st2[0],setRows=st2[1];
		return el('div',{className:'sst-setup-panel'},
			el('h3',null,'SERPsKit SEOTable'),
			el('p',null,'Set the number of columns and rows.'),
			el('div',{className:'sst-setup-fields'},
				el('label',null,'Columns',el('input',{type:'number',min:2,max:20,value:cols,onChange:function(e){setCols(parseInt(e.target.value,10)||2)}})),
				el('label',null,'Rows',el('input',{type:'number',min:1,max:100,value:rows,onChange:function(e){setRows(parseInt(e.target.value,10)||1)}}))
			),
			el('button',{className:'sst-setup-btn',onClick:function(){props.onCreate(defaultData(cols,rows))}},'Create Table')
		);
	}

	function CellEditor(props) {
		var cell=props.cell, selected=props.selected;
		var parts=[];
		parts.push(el('textarea',{key:'txt',className:'sst-cell-input',value:cell.text,rows:1,
			onClick:function(e){e.stopPropagation();props.onSelect();},
			onFocus:function(){props.onSelect();},
			onChange:function(e){var u=cloneData(cell);u.text=e.target.value;props.onChange(u);},
			onInput:function(e){e.target.style.height='auto';e.target.style.height=e.target.scrollHeight+'px';}
		}));
		if(cell.badge&&cell.badge.type) parts.push(el('span',{key:'badge',className:'sst-editor-badge sst-editor-badge-'+cell.badge.type},cell.badge.text||cell.badge.type));
		if(cell.brandTag) parts.push(el('span',{key:'brand',className:'sst-editor-brand-tag'},cell.brandTag));
		if(cell.subtext) parts.push(el('small',{key:'sub',className:'sst-editor-subtext'},cell.subtext));
		return el('td',{className:selected?'sst-cell-selected':'',onClick:function(){props.onSelect();}},parts);
	}

	function EditBlock(props) {
		var attributes=props.attributes, setAttributes=props.setAttributes, blockProps=useBlockProps();
		var rawData=attributes.tableData, caption=attributes.caption||'';
		var selState=useState(null), sel=selState[0], setSel=selState[1];
		var data=null;
		if(rawData){try{data=JSON.parse(rawData)}catch(e){data=null}}
		var s=window.sstSettings||{};

		function save(d){setAttributes({tableData:JSON.stringify(d)})}
		function getCell(){if(!sel||!data)return null;var row=data.rows[sel.r];return row?row.cells[sel.c]||null:null;}

		// Ensure colWidths array exists
		if(data&&!data.colWidths){data.colWidths=[];for(var ci=0;ci<data.headers.length;ci++)data.colWidths.push(0);}

		function addRow(after){if(!data)return;var d=cloneData(data);var cells=[];for(var i=0;i<d.headers.length;i++)cells.push(emptyCell());d.rows.splice((after!==undefined)?after+1:d.rows.length,0,{cells:cells});save(d)}
		function deleteRow(idx){if(!data||data.rows.length<=1)return;var d=cloneData(data);d.rows.splice(idx,1);setSel(null);save(d)}
		function addColumn(after){if(!data)return;var d=cloneData(data);var idx=(after!==undefined)?after+1:d.headers.length;d.headers.splice(idx,0,'New Column');d.aligns.splice(idx,0,'left');d.colWidths.splice(idx,0,0);d.rows.forEach(function(row){row.cells.splice(idx,0,emptyCell())});save(d)}
		function deleteColumn(idx){if(!data||data.headers.length<=1)return;var d=cloneData(data);d.headers.splice(idx,1);d.aligns.splice(idx,1);d.colWidths.splice(idx,1);d.rows.forEach(function(row){row.cells.splice(idx,1)});setSel(null);save(d)}
		function moveRow(fi,dir){if(!data)return;var ti=fi+dir;if(ti<0||ti>=data.rows.length)return;var d=cloneData(data);var m=d.rows.splice(fi,1)[0];d.rows.splice(ti,0,m);setSel({r:ti,c:sel?sel.c:0});save(d)}
		function moveColumn(fi,dir){if(!data)return;var ti=fi+dir;if(ti<0||ti>=data.headers.length)return;var d=cloneData(data);
			var t;t=d.headers[fi];d.headers[fi]=d.headers[ti];d.headers[ti]=t;
			t=d.aligns[fi];d.aligns[fi]=d.aligns[ti];d.aligns[ti]=t;
			t=d.colWidths[fi];d.colWidths[fi]=d.colWidths[ti];d.colWidths[ti]=t;
			d.rows.forEach(function(row){t=row.cells[fi];row.cells[fi]=row.cells[ti];row.cells[ti]=t});
			setSel({r:sel?sel.r:0,c:ti});save(d)}
		function updateHeader(idx,val){var d=cloneData(data);d.headers[idx]=val;save(d)}
		function updateCell(r,c,cd){var d=cloneData(data);d.rows[r].cells[c]=cd;save(d)}
		function updateAlign(idx,val){var d=cloneData(data);d.aligns[idx]=val;save(d)}
		function updateColWidth(idx,val){var d=cloneData(data);d.colWidths[idx]=parseInt(val,10)||0;save(d)}

		if(!data){return el('div',blockProps,el(SetupPanel,{onCreate:function(nd){save(nd)}}));}

		var headingStyle={background:s.heading_bg_color||'#2d6a4f'};
		var headerCells=data.headers.map(function(h,ci){
			return el('th',{key:'h'+ci,style:{textAlign:data.aligns[ci]||'left'}},
				el('input',{type:'text',value:h,placeholder:'Header',onChange:function(e){updateHeader(ci,e.target.value)}})
			);
		});
		var bodyRows=data.rows.map(function(row,ri){
			var cells=row.cells.map(function(cell,ci){
				return el(CellEditor,{key:'c'+ri+'-'+ci,cell:cell,selected:sel&&sel.r===ri&&sel.c===ci,onSelect:function(){setSel({r:ri,c:ci})},onChange:function(u){updateCell(ri,ci,u)}});
			});
			return el('tr',{key:'r'+ri},cells);
		});

		var cellPanel=null, currentCell=getCell();
		if(sel&&currentCell){
			cellPanel=el(PanelBody,{title:'Cell (Row '+(sel.r+1)+', Col '+(sel.c+1)+')',initialOpen:true},
				el(ToggleControl,{label:'Bold / Name style',checked:!!currentCell.bold,onChange:function(v){var u=cloneData(currentCell);u.bold=v;updateCell(sel.r,sel.c,u)}}),
				el(SelectControl,{label:'Badge Type',value:(currentCell.badge&&currentCell.badge.type)||'',options:BADGE_TYPES,onChange:function(v){var u=cloneData(currentCell);u.badge=v?{type:v,text:(u.badge&&u.badge.text)||''}:null;updateCell(sel.r,sel.c,u)}}),
				(currentCell.badge&&currentCell.badge.type)?el(TextControl,{label:'Badge Text',value:(currentCell.badge&&currentCell.badge.text)||'',onChange:function(v){var u=cloneData(currentCell);u.badge.text=v;updateCell(sel.r,sel.c,u)}}):null,
				el(TextControl,{label:'Brand Tag',value:currentCell.brandTag||'',placeholder:'e.g. NEMO',onChange:function(v){var u=cloneData(currentCell);u.brandTag=v;updateCell(sel.r,sel.c,u)}}),
				el(TextControl,{label:'Subtext',value:currentCell.subtext||'',onChange:function(v){var u=cloneData(currentCell);u.subtext=v;updateCell(sel.r,sel.c,u)}})
			);
		}

		var colPanel=null;
		if(sel){
			colPanel=el(PanelBody,{title:'Column Settings',initialOpen:false},
				el(SelectControl,{label:'Text Alignment',value:data.aligns[sel.c]||'left',options:ALIGN_OPTIONS,onChange:function(v){updateAlign(sel.c,v)}}),
				el(TextControl,{label:'Column Width (px)',value:String(data.colWidths[sel.c]||''),placeholder:'auto',onChange:function(v){updateColWidth(sel.c,v)},help:'Enter width in pixels. Leave empty or 0 for auto.'})
			);
		}

		// Column widths panel - all columns at once
		var colWidthsPanel = el(PanelBody,{title:'All Column Widths',initialOpen:false},
			el('p',{style:{fontSize:'12px',color:'#666',marginBottom:'10px'}},'Set width in pixels per column. Leave 0 or empty for auto-fit.'),
			data.headers.map(function(h,ci){
				return el(TextControl,{
					key:'cw'+ci,
					label:h||'Column '+(ci+1),
					value:String(data.colWidths[ci]||''),
					placeholder:'auto',
					type:'number',
					onChange:function(v){updateColWidth(ci,v)}
				});
			})
		);

		var toolbar=el(BlockControls,null,
			el(ToolbarGroup,null,
				el(ToolbarButton,{icon:'table-row-after',label:'Add Row',onClick:function(){addRow(sel?sel.r:data.rows.length-1)}}),
				el(ToolbarButton,{icon:'table-row-delete',label:'Delete Row',onClick:function(){if(sel)deleteRow(sel.r)},disabled:!sel||data.rows.length<=1}),
				el(ToolbarButton,{icon:'table-col-after',label:'Add Column',onClick:function(){addColumn(sel?sel.c:data.headers.length-1)}}),
				el(ToolbarButton,{icon:'table-col-delete',label:'Delete Column',onClick:function(){if(sel)deleteColumn(sel.c)},disabled:!sel||data.headers.length<=1})
			),
			sel?el(ToolbarGroup,null,
				el(ToolbarButton,{icon:'arrow-up-alt',label:'Move Row Up',onClick:function(){moveRow(sel.r,-1)},disabled:sel.r===0}),
				el(ToolbarButton,{icon:'arrow-down-alt',label:'Move Row Down',onClick:function(){moveRow(sel.r,1)},disabled:sel.r>=data.rows.length-1}),
				el(ToolbarButton,{icon:'arrow-left-alt',label:'Move Col Left',onClick:function(){moveColumn(sel.c,-1)},disabled:sel.c===0}),
				el(ToolbarButton,{icon:'arrow-right-alt',label:'Move Col Right',onClick:function(){moveColumn(sel.c,1)},disabled:sel.c>=data.headers.length-1})
			):null
		);

		return el('div',blockProps,
			toolbar,
			el(InspectorControls,null,
				cellPanel,
				colPanel,
				colWidthsPanel,
				el(PanelBody,{title:'Table Options',initialOpen:false},
					el(TextControl,{label:'Caption',value:caption,onChange:function(v){setAttributes({caption:v})}}),
					el(Button,{isDestructive:true,variant:'secondary',onClick:function(){if(confirm('Reset table?')){setAttributes({tableData:''});setSel(null)}}},'Reset Table')
				)
			),
			el('div',{className:'sst-editor-wrap'},
				el('table',{className:'sst-editor-table'},
					el('thead',null,el('tr',{style:headingStyle},headerCells)),
					el('tbody',null,bodyRows)
				)
			),
			el('input',{type:'text',className:'sst-caption-input',value:caption,placeholder:'Table caption (optional)',onChange:function(e){setAttributes({caption:e.target.value})}})
		);
	}

	registerBlock('serpskit/seotable',{
		title:'SEOTable',
		description:'Create SEO-optimized tables with badges, hover effects, and responsive design.',
		icon:'editor-table',
		category:'formatting',
		keywords:['table','comparison','seo','serpskit','review','product'],
		supports:{html:false,align:['wide','full'],anchor:true},
		attributes:{
			tableData:{type:'string',default:''},
			caption:{type:'string',default:''},
			tableId:{type:'string',default:''}
		},
		transforms:{
			from:[{
				type:'block',blocks:['core/table'],
				transform:function(a){return createBlock('serpskit/seotable',{tableData:JSON.stringify(parseCoreTable(a)),caption:a.caption||''})}
			}]
		},
		edit:EditBlock,
		save:function(){return null}
	});

})(window.wp);
