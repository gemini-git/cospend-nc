<template>
	<ListItem
		:class="{ newBill: bill.id === 0}"
		:title="billFormattedTitle"
		:active="selected"
		:bold="selected"
		:details="billDetails"
		:counter-number="deleteCounter"
		:force-display-actions="true"
		@click="onItemClick">
		<template #subtitle>
			{{ parseFloat(bill.amount).toFixed(2) }} ({{ smartPayerName }} → {{ smartOwerNames }})
		</template>
		<template #icon>
			<ColoredAvatar
				:class="{ newBillAvatar: bill.id === 0 }"
				:color="payerColor"
				:size="40"
				:disable-menu="true"
				:disable-tooltip="true"
				:show-user-status="false"
				:is-no-user="payerUserId === ''"
				:user="payerUserId"
				:display-name="payerName" />
			<div v-if="payerDisabled" class="billItemDisabledMask disabled" />
			<div v-if="bill.repeat !== 'n'" class="billItemRepeatMask show">
				<CalendarSyncIcon :size="16" />
			</div>
		</template>
		<template #actions>
			<ActionButton v-if="editionAccess && showDelete && (deletionEnabled || bill.id === 0)"
				:close-after-click="true"
				@click="onDeleteClick">
				<template #icon>
					<component :is="deleteIconComponent"
						class="icon"
						:size="20" />
				</template>
				{{ deleteIconTitle }}
			</ActionButton>
		</template>
		<template #extra>
			<div v-if="editionAccess && !showDelete" class="icon-selector">
				<CheckboxMarkedIcon v-if="selected" class="selected" :size="20" />
				<CheckboxBlankOutlineIcon v-else :size="20" />
			</div>
		</template>
	</ListItem>
</template>

<script>
import CalendarSyncIcon from 'vue-material-design-icons/CalendarSync'
import CheckboxMarkedIcon from 'vue-material-design-icons/CheckboxMarked'
import CheckboxBlankOutlineIcon from 'vue-material-design-icons/CheckboxBlankOutline'
import DeleteIcon from 'vue-material-design-icons/Delete'
import UndoIcon from 'vue-material-design-icons/Undo'
import cospend from '../state'
import { generateUrl } from '@nextcloud/router'
import moment from '@nextcloud/moment'
import ListItem from '@nextcloud/vue/dist/Components/ListItem'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ColoredAvatar from './ColoredAvatar'
import { reload, Timer, getCategory, getPaymentMode, getSmartMemberName } from '../utils'

export default {
	name: 'BillListItem',

	components: {
		ListItem,
		ColoredAvatar,
		CalendarSyncIcon,
		UndoIcon,
		DeleteIcon,
		CheckboxBlankOutlineIcon,
		CheckboxMarkedIcon,
		ActionButton,
	},

	props: {
		bill: {
			type: Object,
			required: true,
		},
		projectId: {
			type: String,
			required: true,
		},
		editionAccess: {
			type: Boolean,
			required: true,
		},
		index: {
			type: Number,
			required: true,
		},
		nbbills: {
			type: Number,
			required: true,
		},
		selected: {
			type: Boolean,
			required: true,
		},
		showDelete: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			deleteCounter: 0,
			timer: null,
		}
	},

	computed: {
		timerOn() {
			return this.deleteCounter > 0
		},
		billUrl() {
			return generateUrl('/apps/cospend/p/{projectId}/b/{billId}', { projectId: this.projectId, billId: this.bill.id })
		},
		members() {
			return cospend.members[this.projectId]
		},
		payerDisabled() {
			return this.bill.id !== 0 && !this.members[this.bill.payer_id].activated
		},
		payerUserId() {
			return this.bill.id !== 0 && this.members[this.bill.payer_id]
				? this.members[this.bill.payer_id].userid || ''
				: ''
		},
		payerColor() {
			return (this.bill.payer_id === 0 || this.bill.id === 0)
				? '000000'
				: this.members[this.bill.payer_id]
					? this.members[this.bill.payer_id].color
					: '000000'
		},
		payerName() {
			return (this.bill.payer_id === 0 || this.bill.id === 0)
				? '*'
				: this.members[this.bill.payer_id]
					? this.members[this.bill.payer_id].name
					: ''
		},
		deletionEnabled() {
			return !cospend.projects[this.projectId].deletion_disabled
		},
		billFormattedTitle() {
			const links = this.bill.what.match(/https?:\/\/[^\s]+/gi) || []
			let linkChars = ''
			for (let i = 0; i < links.length; i++) {
				linkChars = linkChars + '  🔗'
			}
			let paymentmodeChar = ''
			let categoryChar = ''
			if (parseInt(this.bill.categoryid) !== 0) {
				categoryChar = getCategory(this.projectId, this.bill.categoryid).icon + ' '
			}
			if (parseInt(this.bill.paymentmodeid) !== 0) {
				paymentmodeChar = getPaymentMode(this.projectId, this.bill.paymentmodeid).icon + ' '
			}
			return paymentmodeChar + categoryChar + this.bill.what.replace(/https?:\/\/[^\s]+/gi, '') + linkChars
		},
		smartPayerName() {
			return this.bill.payer_id !== 0
				? getSmartMemberName(this.projectId, this.bill.payer_id)
				: ''
		},
		smartOwerNames() {
			const owerIds = this.bill.owerIds
			// get missing members
			let nbMissingEnabledMembers = 0
			const missingEnabledMemberIds = []
			for (const memberid in this.members) {
				if (this.members[memberid].activated
					&& !owerIds.includes(parseInt(memberid))) {
					nbMissingEnabledMembers++
					missingEnabledMemberIds.push(memberid)
				}
			}

			// 4 cases : all, all except 1, all except 2, custom
			if (nbMissingEnabledMembers === 0) {
				return t('cospend', 'Everyone')
			} else if (nbMissingEnabledMembers === 1 && owerIds.length > 2) {
				const mName = getSmartMemberName(this.projectId, missingEnabledMemberIds[0])
				return t('cospend', 'Everyone except {member}', { member: mName })
			} else if (nbMissingEnabledMembers === 2 && owerIds.length > 2) {
				const mName1 = getSmartMemberName(this.projectId, missingEnabledMemberIds[0])
				const mName2 = getSmartMemberName(this.projectId, missingEnabledMemberIds[1])
				const mName = t('cospend', '{member1} and {member2}', { member1: mName1, member2: mName2 })
				return t('cospend', 'Everyone except {member}', { member: mName })
			} else {
				let owerNames = ''
				let mid
				for (let i = 0; i < owerIds.length; i++) {
					mid = owerIds[i]
					if (!(mid in this.members)) {
						reload(t('cospend', 'Member list is not up to date. Reloading in 5 sec.'))
						return
					}
					owerNames = owerNames + getSmartMemberName(this.projectId, mid) + ', '
				}
				owerNames = owerNames.replace(/, $/, '')
				return owerNames
			}
		},
		billDate() {
			const billMom = moment.unix(this.bill.timestamp)
			return billMom.format('L')
		},
		billIndexText() {
			return '[' + this.index + '/' + this.nbbills + ']'
		},
		deleteIconComponent() {
			return this.timerOn
				? UndoIcon
				: DeleteIcon
		},
		deleteIconTitle() {
			return this.timerOn
				? t('cospend', 'Cancel')
				: t('cospend', 'Delete this bill')
		},
		billDetails() {
			return this.selected
				? this.billIndexText + ' ' + this.billDate
				: this.billDate
		},
	},

	mounted() {
	},

	methods: {
		onItemClick() {
			this.$emit('clicked', this.bill)
		},
		onDeleteClick(e) {
			// stop timer
			if (this.timerOn) {
				this.deleteCounter = 0
				if (this.timer) {
					this.timer.pause()
					delete this.timer
				}
			} else {
				if (this.bill.id === 0) {
					this.$emit('delete', this.bill)
				} else {
					// start timer
					this.deleteCounter = 7
					this.timerLoop()
				}
			}
		},
		timerLoop() {
			// on each loop, check if finished or not
			if (this.timerOn) {
				this.timer = new Timer(() => {
					this.deleteCounter--
					this.timerLoop()
				}, 1000)
			} else {
				this.$emit('delete', this.bill)
			}
		},
		onSelectorClick(e) {
			this.$nextTick(() => this.onItemClick())
		},
	},
}
</script>

<style scoped lang="scss">
::v-deep .newBillAvatar * {
	color: var(--color-main-text) !important;
}

.icon-selector {
	display: flex;
	justify-content: right;
	padding-right: 8px;
	position: absolute;
	right: 14px;
	bottom: 12px;
}

::v-deep .billItemDisabledMask.disabled {
	display: block;
	width: 42px;
	height: 42px;
	background: url('../../css/images/forbidden.svg') no-repeat;
	position: absolute;
	left: 7px;
}

::v-deep .billItemRepeatMask.show {
	display: block;
	color: var(--color-main-text);
	width: 16px;
	height: 16px;
	margin: 33px 0 0 -4px;
	position: absolute;
}
</style>
