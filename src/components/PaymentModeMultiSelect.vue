<template>
	<Multiselect
		:value="selectedPmItem"
		class="pmMultiSelect multiSelect"
		label="displayName"
		track-by="id"
		:disabled="disabled"
		:placeholder="placeholder"
		:options="formattedOptions"
		:user-select="false"
		:internal-search="true"
		@input="onPmSelected" />
</template>

<script>
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'

export default {
	name: 'PaymentModeMultiSelect',

	components: {
		Multiselect,
	},

	props: {
		disabled: {
			type: Boolean,
			default: false,
		},
		placeholder: {
			type: String,
			required: true,
		},
		paymentModes: {
			type: Array,
			required: true,
		},
		value: {
			type: Object,
			default: () => null,
		},
	},

	data() {
		return {}
	},

	computed: {
		formattedOptions() {
			return this.paymentModes.map(pm => {
				return {
					...pm,
					displayName: pm.icon + ' ' + pm.name,
				}
			})
		},
		selectedPmItem() {
			return this.value
				? {
					...this.value,
					displayName: this.value.icon + ' ' + this.value.name,
				}
				: null
		},
	},

	methods: {
		onPmSelected(selected) {
			this.$emit('input', selected)
		},
	},
}
</script>

<style scoped lang="scss">
.pmMultiSelect {
	height: 44px;
}
</style>
