<template>
	<AppContentDetails class="settlement-content">
		<h2 id="settlementTitle">
			<ReimburseIcon :class="{ 'icon-loading': loading }" :size="20" />
			<span>
				{{ t('cospend', 'Settlement of project {name}', { name: project.name }, undefined, { escape: false }) }}
			</span>
			<Button @click="onExportClick">
				<template #icon>
					<ContentSaveIcon :size="20" />
				</template>
				{{ t('cospend', 'Export') }}
			</Button>
			<Button v-if="editionAccess" @click="onAutoSettleClick">
				<template #icon>
					<PlusIcon :size="20" />
				</template>
				{{ t('cospend', 'Add these payments to the project') }}
			</Button>
		</h2>
		<div id="settlement-options">
			<div id="center-settle-div" class="centered-option">
				<label for="settle-member-center">{{ t('cospend', 'Center settlement on') }}</label>
				<select id="settle-member-center" v-model="centeredOn" @change="onChangeCenterMember">
					<option value="0">
						{{ t('cospend', 'Nobody (optimal)') }}
					</option>
					<option
						v-for="(member, mid) in members"
						:key="mid"
						:value="mid">
						{{ member.name }}
					</option>
				</select>
			</div>
			<div id="max-date-settle-div" class="centered-option">
				<label for="max-date">
					{{ t('cospend', 'Settlement date') }}
				</label>
				<Button @click="onDateInfoClicked">
					<template #icon>
						<InformationVariantIcon :size="20" />
					</template>
				</Button>
				<DatetimePicker
					id="max-date"
					v-model="maxDate"
					class="datetime-picker"
					type="datetime"
					:placeholder="t('cospend', 'When?')"
					:minute-step="5"
					:show-second="false"
					:formatter="format"
					:clearable="true"
					confirm
					@change="onChangeMaxDate" />
				<Button
					v-tooltip.bottom="{ content: t('cospend', 'Set to beginning of this day') }"
					@click="onDayBeginningClicked">
					<template #icon>
						<CalendarTodayIcon :size="20" />
					</template>
				</Button>
				<Button
					v-tooltip.bottom="{ content: t('cospend', 'Set to beginning of this week') }"
					@click="onWeekBeginningClicked">
					<template #icon>
						<CalendarWeekIcon :size="20" />
					</template>
				</Button>
				<Button
					v-tooltip.bottom="{ content: t('cospend', 'Set to beginning of this month') }"
					@click="onMonthBeginningClicked">
					<template #icon>
						<CalendarMonthIcon :size="20" />
					</template>
				</Button>
			</div>
		</div>
		<hr>

		<h3>
			{{ maxTs ? t('cospend', 'Settlement plan on {date}', { date: formattedMaxDate }) : t('cospend', 'Settlement plan') }}
		</h3>
		<div v-if="loading" class="loading loading-animation" />
		<v-table v-else-if="transactions"
			id="settlementTable"
			class="coloredTable avatarTable"
			:data="transactions">
			<thead slot="head">
				<v-th sort-key="from">
					{{ t('cospend', 'Who pays?') }}
				</v-th>
				<v-th sort-key="to">
					{{ t('cospend', 'To whom?') }}
				</v-th>
				<v-th sort-key="amount">
					{{ t('cospend', 'How much?') }}
				</v-th>
			</thead>
			<tbody slot="body" slot-scope="{displayData}">
				<tr v-for="value in displayData" :key="value.from + ':' + value.to">
					<td :style="'border: 2px solid #' + myGetMemberColor(value.from) + ';'">
						<div class="owerAvatar">
							<ColoredAvatar
								class="itemAvatar"
								:color="getMemberColor(value.from)"
								:size="24"
								:disable-menu="true"
								:disable-tooltip="true"
								:show-user-status="false"
								:is-no-user="getMemberUserId(value.from) === ''"
								:user="getMemberUserId(value.from)"
								:display-name="getMemberName(value.from)" />
							<div v-if="isMemberDisabled(value.from)" class="disabledMask" />
						</div>
						{{ myGetSmartMemberName(project.id, value.from) }}
					</td>
					<td :style="'border: 2px solid #' + myGetMemberColor(value.to) + ';'">
						<div class="owerAvatar">
							<ColoredAvatar
								class="itemAvatar"
								:color="getMemberColor(value.to)"
								:size="24"
								:disable-menu="true"
								:disable-tooltip="true"
								:show-user-status="false"
								:is-no-user="getMemberUserId(value.to) === ''"
								:user="getMemberUserId(value.to)"
								:display-name="getMemberName(value.to)" />
							<div v-if="isMemberDisabled(value.to)" class="disabledMask" />
						</div>
						{{ myGetSmartMemberName(project.id, value.to) }}
					</td>
					<td>{{ value.amount.toFixed(precision) }}</td>
				</tr>
			</tbody>
		</v-table>
		<EmptyContent v-else
			class="central-empty-content">
			<template #icon>
				<ReimburseIcon />
			</template>
			{{ t('cospend', 'No transactions found') }}
		</EmptyContent>

		<h3>
			{{ maxTs ? t('cospend', 'Balances on {date}', { date: formattedMaxDate }) : t('cospend', 'Balances') }}
		</h3>
		<div v-if="loading" class="loading loading-animation" />
		<v-table v-else-if="balances"
			id="balanceTable"
			class="coloredTable avatarTable"
			:data="balances">
			<thead slot="head">
				<v-th sort-key="member.name">
					{{ t('cospend', 'Member name') }}
				</v-th>
				<v-th sort-key="balance">
					{{ t('cospend', 'Balance') }}
				</v-th>
			</thead>
			<tbody slot="body" slot-scope="{displayData}">
				<tr v-for="value in displayData"
					:key="value.mid">
					<td :style="'border: 2px solid #' + myGetMemberColor(value.mid) + ';'">
						<div class="owerAvatar">
							<ColoredAvatar
								class="itemAvatar"
								:color="getMemberColor(value.mid)"
								:size="24"
								:disable-menu="true"
								:disable-tooltip="true"
								:show-user-status="false"
								:is-no-user="getMemberUserId(value.mid) === ''"
								:user="getMemberUserId(value.mid)"
								:display-name="getMemberName(value.mid)" />
							<div v-if="isMemberDisabled(value.mid)" class="disabledMask" />
						</div>{{ myGetSmartMemberName(project.id, value.mid) }}
					</td>
					<td :class="getBalanceClass(value.balance)"
						:style="'border: 2px solid #' + myGetMemberColor(value.mid) +';'">
						{{ value.balance.toFixed(2) }}
					</td>
				</tr>
			</tbody>
			<tfoot />
		</v-table>
		<EmptyContent v-else
			class="central-empty-content">
			<template #icon>
				<CospendIcon />
			</template>
			{{ t('cospend', 'No balances found') }}
		</EmptyContent>

		<hr>
		<h2 class="individualTitle">
			<Button @click="onIndividualInfoClicked">
				<template #icon>
					<InformationVariantIcon :size="20" />
				</template>
			</Button>
			<span>{{ t('cospend', 'Individual reimbursement') }}</span>
		</h2>
		<div id="individual-form">
			<select id="individual-payer" v-model="individualPayerId" @change="onChangeIndividual">
				<option value="0">
					{{ t('cospend', 'Payer') }}
				</option>
				<option
					v-for="member in membersWithNegativeBalance"
					:key="member.id"
					:value="member.id">
					{{ member.name }}
				</option>
			</select>
			→
			<select id="individual-receiver" v-model="individualReceiverId" @change="onChangeIndividual">
				<option :value="0">
					{{ t('cospend', 'Receiver') }}
				</option>
				<option
					v-for="member in receiverCandidates"
					:key="member.id"
					:value="member.id">
					{{ member.name }}
				</option>
			</select>
			<Button v-if="individualPayerId && individualReceiverId"
				@click="createIndividual">
				{{ t('cospend', 'Create bill ({amount})', { amount: (-members[individualPayerId].balance).toFixed(precision) }) }}
			</Button>
		</div>
	</AppContentDetails>
</template>

<script>
import Button from '@nextcloud/vue/dist/Components/Button'
import InformationVariantIcon from 'vue-material-design-icons/InformationVariant'
import ContentSaveIcon from 'vue-material-design-icons/ContentSave'
import PlusIcon from 'vue-material-design-icons/Plus'
import CalendarTodayIcon from 'vue-material-design-icons/CalendarToday'
import CalendarWeekIcon from 'vue-material-design-icons/CalendarWeek'
import CalendarMonthIcon from 'vue-material-design-icons/CalendarMonth'
import { showSuccess, showError } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'
import { getLocale } from '@nextcloud/l10n'
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import DatetimePicker from '@nextcloud/vue/dist/Components/DatetimePicker'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import ColoredAvatar from './components/ColoredAvatar'

import { getSmartMemberName } from './utils'
import cospend from './state'
import * as constants from './constants'
import * as network from './network'
import CospendIcon from './components/CospendIcon'
import ReimburseIcon from './components/ReimburseIcon'

export default {
	name: 'Settlement',

	components: {
		ReimburseIcon,
		CospendIcon,
		ColoredAvatar,
		AppContentDetails,
		DatetimePicker,
		EmptyContent,
		Button,
		ContentSaveIcon,
		PlusIcon,
		InformationVariantIcon,
		CalendarTodayIcon,
		CalendarWeekIcon,
		CalendarMonthIcon,
	},

	props: {
		projectId: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			loading: false,
			transactions: null,
			balancesObject: null,
			centeredOn: 0,
			maxDate: null,
			locale: getLocale(),
			format: {
				stringify: this.stringify,
				parse: this.parse,
			},
			// individual reimbursement
			individualPayerId: 0,
			individualReceiverId: 0,
		}
	},

	computed: {
		project() {
			return cospend.projects[this.projectId]
		},
		members() {
			return cospend.members[this.projectId]
		},
		membersWithNegativeBalance() {
			return Object.values(this.members).filter((m) => {
				return m.balance <= -0.01
			})
		},
		receiverCandidates() {
			return Object.values(this.members).filter((m) => {
				return m.id !== this.individualPayerId
			})
		},
		editionAccess() {
			return (this.project.myaccesslevel >= constants.ACCESS.PARTICIPANT)
		},
		precision() {
			return this.project.precision || 2
		},
		maxTs() {
			return this.maxDate
				? moment(this.maxDate).unix()
				: null
		},
		formattedMaxDate() {
			return this.stringify(this.maxDate)
		},
		balances() {
			return this.balancesObject
				? Object.keys(this.balancesObject).map((k) => {
					return {
						mid: k,
						balance: this.balancesObject[k],
					}
				})
				: null
		},
	},

	watch: {
		projectId() {
			this.transactions = []
			this.centeredOn = 0
			this.getSettlement()
		},
	},

	mounted() {
		this.getSettlement()
	},

	methods: {
		myGetSmartMemberName(pid, mid) {
			return getSmartMemberName(pid, mid)
		},
		myGetMemberColor(mid) {
			return this.members[mid].color
		},
		getMemberName(mid) {
			return this.members[mid].name
		},
		getMemberColor(mid) {
			return this.members[mid].color || ''
		},
		getMemberUserId(mid) {
			return this.members[mid].userid || ''
		},
		isMemberDisabled(mid) {
			return !this.members[mid].activated
		},
		getBalanceClass(balance) {
			let balanceClass = ''
			if (balance > 0) {
				balanceClass = 'balancePositive'
			} else if (balance < 0) {
				balanceClass = 'balanceNegative'
			}
			return balanceClass
		},
		onChangeCenterMember(e) {
			this.getSettlement(e.target.value)
		},
		onChangeMaxDate(e) {
			this.getSettlement(this.centeredOn)
		},
		getSettlement(centeredOn = null) {
			this.loading = true
			network.getSettlement(this.project.id, centeredOn, this.maxTs).then((response) => {
				this.getSettlementSuccess(response.data)
			}).catch((error) => {
				this.getSettlementFail()
				showError(
					t('cospend', 'Failed to get settlement')
					+ ': ' + error.response?.request?.responseText
				)
			}).then(() => {
				this.loading = false
			})
		},
		getSettlementSuccess(response) {
			if (Array.isArray(response.transactions) && response.transactions.length > 0) {
				this.transactions = response.transactions
				this.balancesObject = response.balances
			} else {
				this.getSettlementFail()
			}
		},
		getSettlementFail() {
			this.transactions = null
			this.balancesObject = null
		},
		onExportClick() {
			this.exportSettlement()
		},
		onAutoSettleClick() {
			this.autoSettlement()
		},
		autoSettlement() {
			network.autoSettlement(this.projectId, this.centeredOn, this.maxTs, this.precision, this.autoSettlementSuccess)
		},
		autoSettlementSuccess() {
			this.$emit('auto-settled', this.projectId)
			showSuccess(t('cospend', 'Project settlement bills added.'))
			this.getSettlement(this.centeredOn)
		},
		exportSettlement() {
			network.exportSettlement(this.projectId, this.centeredOn, this.maxTs, this.exportSettlementSuccess)
		},
		exportSettlementSuccess(response) {
			showSuccess(t('cospend', 'Project settlement exported in {path}', { path: response.path }))
		},
		// datetime picker formatter
		stringify(date) {
			return moment(date).locale(this.locale).format('LLL')
		},
		parse(value) {
			return moment(value, 'LLL', this.locale).toDate()
		},
		onDateInfoClicked() {
			OC.dialogs.info(
				t('cospend', 'Set a maximum date to only consider bills until then.')
				+ ' '
				+ t('cospend', 'Useful if you want to settle at a precise date and ignore the bills created since then.')
				+ ' '
				+ t('cospend', 'Automatic settlement will create bills one second before the maximum date.'),
				t('cospend', 'Info')
			)
		},
		onIndividualInfoClicked() {
			OC.dialogs.info(
				t('cospend', 'This feature is useful when a member with a negative balance wants to get out of the project but you don\'t want to make a full settlement plan.')
				+ ' '
				+ t('cospend', 'Select a payer who wants to get a zero balance, then a receiver who will be the only one to get the reimbursement money.')
				+ ' '
				+ t('cospend', 'Make sure the "real" reimbursement has been done between those 2 members in real life. Then press "Create bill" to automatically create the corresponding bill.'),
				t('cospend', 'Info')
			)
		},
		onDayBeginningClicked() {
			const begin = moment()
				.millisecond(0)
				.second(0)
				.minute(0)
				.hour(0)
			this.maxDate = begin.toDate()
			this.getSettlement(this.centeredOn)
		},
		onWeekBeginningClicked() {
			const begin = moment()
				.millisecond(0)
				.second(0)
				.minute(0)
				.hour(0)
				.day(1)
			this.maxDate = begin.toDate()
			this.getSettlement(this.centeredOn)
		},
		onMonthBeginningClicked() {
			const begin = moment()
				.millisecond(0)
				.second(0)
				.minute(0)
				.hour(0)
				.date(1)
			this.maxDate = begin.toDate()
			this.getSettlement(this.centeredOn)
		},
		// individual reimbursement
		onChangeIndividual(e) {
			if (this.individualPayerId === this.individualReceiverId) {
				this.individualReceiverId = 0
			}
		},
		createIndividual() {
			const req = {
				what: t('cospend', 'Reimbursement'),
				timestamp: moment().unix(),
				payer: this.individualPayerId,
				payed_for: '' + this.individualReceiverId,
				amount: -this.members[this.individualPayerId].balance,
				repeat: 'n',
				categoryid: '-11',
			}
			network.createBill(this.projectId, req).then((response) => {
				this.$emit('auto-settled', this.projectId)
				this.individualPayerId = 0
				this.individualReceiverId = 0
			}).catch((error) => {
				showError(
					t('cospend', 'Failed to create bill')
					+ ': ' + error.response?.request?.responseText
				)
			})
		},
	},
}
</script>

<style scoped lang="scss">
#settlement-options {
	.centered-option {
		display: flex;
		justify-content: center;
		align-items: center;
		margin: 10px 0 10px 0;

		> * {
			margin: 0 5px 0 5px;
		}

		label {
			margin-right: 5px;
		}
	}
}

.settlement-content {
	margin-left: auto;
	margin-right: auto;

	>h2,
	>h3 {
		text-align: center;
	}

	.individualTitle {
		display: flex;
		align-items: center;
		justify-content: center;
		> * {
			margin: 0 5px 0 5px;
		}
	}

	#individual-form {
		display: flex;
		justify-content: center;
		align-items: center;
		margin-bottom: 50px;
	}
}

#settlementTitle {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: center;
	padding: 20px 0px 20px 0px;
	> * {
		margin: 0 10px 0 10px;
	}
}

#balanceTable {
	display: table;
	margin: 20px auto 20px auto;
}

#settlementTable {
	display: table;
	margin: 20px auto 20px auto;

	td {
		border: 1px solid var(--color-border-dark);
		padding: 0 5px 0 0;
		text-align: left;
	}

	td:last-child {
		text-align: right;
		padding-right: 2px;
	}
}

.loading-animation {
	height: 70px;
}
</style>
