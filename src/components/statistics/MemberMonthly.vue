<template>
	<div>
		<div class="tableWrapper"
			@mouseleave="selectedMemberDataset = null ; hoveredTableMonth = null">
			<v-table
				class="memberMonthlyTable coloredTable avatarTable"
				:data="memberMonthlyStats">
				<thead slot="head">
					<v-th sort-key="member.name">
						{{ t('cospend', 'Member/Month') }}
					</v-th>
					<v-th v-for="(st, month) in stats"
						:key="month"
						:sort-key="month"
						:class="{ selected: selectedMemberMonthlyCol === Object.keys(stats).indexOf(month) }">
						{{ month }}
					</v-th>
				</thead>
				<tbody slot="body" slot-scope="{displayData}">
					<tr v-for="value in displayData"
						:key="value.member.id"
						:class="{ 'all-members': value.member.id === 0 }"
						@mouseenter="selectedMemberDataset = value.member.id">
						<td :style="'border: 2px solid #' + myGetMemberColor(value.member.id) + ';'">
							<div v-if="value.member.id !== 0"
								class="owerAvatar">
								<ColoredAvatar
									class="itemAvatar"
									:color="getMemberColor(value.member.id)"
									:size="24"
									:disable-menu="true"
									:disable-tooltip="true"
									:show-user-status="false"
									:is-no-user="getMemberUserId(value.member.id) === ''"
									:user="getMemberUserId(value.member.id)"
									:display-name="getMemberName(value.member.id)" />
								<div v-if="isMemberDisabled(value.member.id)" class="disabledMask" />
							</div>{{ (value.member.id !== 0) ? myGetSmartMemberName(value.member.id) : value.member.name }}
						</td>
						<td v-for="(st, month) in stats"
							:key="month"
							:class="{ selected: selectedMemberMonthlyCol === Object.keys(stats).indexOf(month) }"
							:style="'border: 2px solid #' + myGetMemberColor(value.member.id) + ';'"
							@mouseenter="hoveredTableMonth = month">
							{{ value[month].toFixed(2) }}
						</td>
					</tr>
				</tbody>
			</v-table>
		</div>
		<div class="memberMonthlyChart"
			@mouseleave="selectedMemberMonthlyCol = null">
			<LineChartJs v-if="stats"
				:chart-data="chartDataWithSelection"
				:chart-options="memberMonthlyChartOptions" />
			<div v-else-if="loadingStats" class="loading loading-stats-animation" />
		</div>
	</div>
</template>

<script>
import ColoredAvatar from '../ColoredAvatar'

import { getSmartMemberName } from '../../utils'
import cospend from '../../state'
import LineChartJs from '../LineChartJs'

export default {
	name: 'MemberMonthly',

	components: {
		ColoredAvatar,
		LineChartJs,
	},

	props: {
		projectId: {
			type: String,
			required: true,
		},
		stats: {
			type: Object,
			required: true,
		},
		memberIds: {
			type: Array,
			required: true,
		},
		realMonths: {
			type: Array,
			required: true,
		},
		chartTitle: {
			type: String,
			required: true,
		},
		baseLineChartOptions: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			cospend,
			loadingStats: false,
			selectedMemberMonthlyCol: null,
			selectedMemberDataset: null,
			hoveredTableMonth: null,
		}
	},

	computed: {
		members() {
			return cospend.members[this.projectId]
		},
		memberMonthlyStats() {
			const memberIds = this.memberIds
			const mids = memberIds.slice()
			mids.push('0')
			return mids.map((mid) => {
				const row = {
					member: mid === '0' ? { name: t('cospend', 'All members'), id: 0 } : cospend.members[this.projectId][mid],
				}
				for (const month in this.stats) {
					row[month] = this.stats[month][mid]
				}
				return row
			})
		},
		chartData() {
			const memberDatasets = []
			let member
			const memberDict = {
				...this.members,
				0: {
					name: t('cospend', 'All members'),
					color: this.myGetMemberColor(0),
				},
			}
			for (const mid in memberDict) {
				member = memberDict[mid]
				const paid = []
				for (const month of this.realMonths) {
					if (mid in this.stats[month]) {
						paid.push(this.stats[month][mid].toFixed(2))
					}
				}
				// check if data is complete (would be better to be sure of member list, like get it from the stats request)
				if (paid.length !== this.realMonths.length) {
					continue
				}

				const dataset = {
					id: parseInt(mid),
					label: member.name,
					// FIXME hacky way to change alpha channel:
					backgroundColor: '#' + member.color + '4D',
					pointBackgroundColor: '#' + member.color,
					borderColor: '#' + member.color,
					pointHighlightStroke: '#' + member.color,
					// lineTension: 0.2,
					data: paid,
					hidden: parseInt(mid) === 0,
					pointRadius: Array(this.realMonths.length).fill(0),
					fill: false,
					order: 0,
					borderWidth: 3,
				}
				memberDatasets.push(dataset)
			}
			return {
				labels: this.realMonths,
				datasets: memberDatasets,
			}
		},
		chartDataWithSelection() {
			if (this.selectedMemberDataset) {
				// row index
				const selectedDatasetIndex = this.chartData.datasets.findIndex((ds) => {
					return ds.id === this.selectedMemberDataset
				})
				// column index
				const hoveredTableMonthIndex = this.chartData.labels.indexOf(this.hoveredTableMonth)
				const selectedDatasetPointRadius = Array(this.chartData.labels.length).fill(0)
				selectedDatasetPointRadius[hoveredTableMonthIndex] = 10

				return {
					labels: this.chartData.labels,
					datasets: [
						...this.chartData.datasets.slice(0, selectedDatasetIndex),
						{
							...this.chartData.datasets[selectedDatasetIndex],
							pointRadius: selectedDatasetPointRadius,
							order: -1,
							borderWidth: 5,
							fill: 'origin',
						},
						...this.chartData.datasets.slice(selectedDatasetIndex + 1, this.chartData.datasets.length),
					],
				}
			} else {
				return {
					labels: this.chartData.labels,
					datasets: this.chartData.datasets,
				}
			}
		},
		memberMonthlyChartOptions() {
			return {
				...this.baseLineChartOptions,
				plugins: {
					...this.baseLineChartOptions.plugins,
					title: {
						display: true,
						text: this.chartTitle,
					},
				},
				onHover: this.onMemberMonthlyChartHover,
			}
		},
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onMemberMonthlyChartHover(event, data) {
			if (data.length > 0 && data[0].index !== undefined) {
				this.selectedMemberMonthlyCol = data[0].index
			}
		},
		isMemberDisabled(mid) {
			return !this.members[mid].activated
		},
		getMemberColor(mid) {
			return this.members[mid].color || ''
		},
		getMemberUserId(mid) {
			return this.members[mid].userid || ''
		},
		getMemberName(mid) {
			return this.members[mid].name
		},
		myGetSmartMemberName(mid) {
			let smartName = getSmartMemberName(this.projectId, mid)
			if (smartName === t('cospend', 'You')) {
				smartName += ' (' + this.members[mid].name + ')'
			}
			return smartName
		},
		myGetMemberColor(mid) {
			if (mid === 0) {
				return '999999'
			} else {
				return this.members[mid].color
			}
		},
	},
}
</script>

<style scoped lang="scss">
.memberMonthlyTable {
	overflow: scroll;
	max-height: 500px;
	th {
		position: sticky;
		top: 0;
		z-index: 9;
		background-color: var(--color-main-background);
	}
	th:first-child,
	td:first-child {
		position: sticky;
		left: 0;
		z-index: 10;
		background-color: var(--color-main-background);
	}
	th:last-child,
	td:last-child {
		position: sticky;
		right: 0;
		z-index: 10;
		background-color: var(--color-main-background);
	}

	th.selected,
	td.selected {
		background-color: var(--color-background-dark);
		font-weight: bold;
		color: var(--color-success);
	}
	tr.all-members td:first-child {
		padding: 0px 5px 0px 5px;
	}
}
</style>
