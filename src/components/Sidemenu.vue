<template>
	<div class="action-buttons" :class="sidebarOpen ? 'action-buttons-sidebaropen' : ''">
		<span class="">
			<Button v-for="item in notOverflowMenu" :key="item.id"
					:class="item.icon"
					@click="item.callback"
			>{{ item.title }}</Button>
			<Actions :open.sync="actionsOpen" container=".action-buttons" menu-align="right">
				<ActionButton v-for="item in overflowMenu" :key="item.id"
							  :icon="item.icon"
							  @click="item.callback"
				>{{ item.title }}
				</ActionButton>
			</Actions>
		</span>

		<br>
		<span class="statustext">state</span>
	</div>
</template>

<script>

import {
	Actions,
	ActionButton,
	AppContent,
	Modal,
	Tooltip,
	isMobile,
} from '@nextcloud/vue'
import ConflictSolution from "./ConflictSolution";
import PencilOffIcon from "vue-material-design-icons/PencilOff";
import SyncAlertIcon from "vue-material-design-icons/SyncAlert";
import TheEditor from "./EditorEasyMDE";
import ThePreview from "./EditorMarkdownIt";

export default {
	name: "Sidemenu",

	components: {
		Actions,
		ActionButton,
		AppContent,
		Modal,
	},


	data() {
		return {
			items: [],
			actionsOpen: false
		}
	},

	methods: {
		addEntry(name, icon, callbackFunction, hidden) {
			const getHighestId = function(array){
				var id = 0
				array.forEach(function(element) {
					if(element.id>id){
						id = element.id
					}
				});
				return id
			}

			const newid = getHighestId(this.items) + 1
			const menuitem = { title: name, icon: icon, callback: callbackFunction, hidden: hidden, id: newid }
			this.items.push(menuitem)
			this.$forceUpdate();
			return newid
		},
		getFilteredArray(hidden) {
			const elements=[]
			this.items.forEach(function(element) {
				if(element.hidden == hidden){
					elements.push(element)
				}
			})
			return elements
		},
		updateEntry(id, field, value, updateImmediately=true) {
			for (let index = 0; index < this.items.length; index++) {
				if(this.items[index].id == id){
					this.items[index][field]=value
				}
			}
			if(updateImmediately){
				this.$forceUpdate()
			}
		},
		removeID(id) {
			const elements=[]
			this.items.forEach(function(element) {
				if(element.id != id){
					elements.push(element)
				}
			})
			this.items = elements
			this.$forceUpdate()
		},
	},
	computed: {
		notOverflowMenu: function () {
			return this.getFilteredArray(false)
		},
		overflowMenu: function () {
			return this.getFilteredArray(true)
		}
	}
}
</script>

<style scoped>


/* main editor button */
.action-buttons {
	position: fixed;
	top: 50px;
	right: 20px;
	/*width: 44px;*/
	margin-top: 1em;
	z-index: 2000;
	/*right: 20%;*/
}

.action-buttons-sidebaropen {
	right: 27vw !important;
	transition: right 130ms;
}

.action-buttons .action-error {
	background-color: var(--color-error);
	margin-top: 1ex;
}


.action-buttons button, button:not(.button-vue){
	/* width: 20px !important; */
	padding: 0px !important;
}

.statustext{
	padding: 0.5em;
	color: #7b7b7b;
	display: flex;
	justify-content: center
}
</style>
