<template>
	<div class="action-buttons" :class="sidebarOpen ? 'action-buttons-sidebaropen' : ''">
		<Button v-for="item in notOverflowMenu" :key="item.id"
				:class="item.icon"
				@click="item.callback"
		>{{ item.title }}</Button>
		<Button v-for="item in overflowMenu" :key="item.id"
				:class="item.icon"
				@click="item.callback"
		>{{ item.title }}</Button>
		<br>
		<span class="statustext">state</span>
	</div>
</template>

<script>
export default {
	name: "Sidemenu",
	data() {
		return { items: []}
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
		}
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


.action-buttons button {
	padding: 15px;
	margin: 5px;
	float: left;
}

.statustext{
	padding: 0.5em;
	color: #7b7b7b;
	display: flex;
	justify-content: center
}
</style>
