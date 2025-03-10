<template>
	<AppNavigationItem v-if="deleting"
		:title="t('cospend', 'Are you sure?')"
		:undo="true"
		@undo="cancelDeletion">
		<template #counter>
			<vac :end-time="new Date().getTime() + (7000)">
				<template #process="{ timeObj }">
					<span>{{ `${timeObj.s}` }}</span>
				</template>
				<!--template v-slot:finish>
					<span>Done!</span>
				</template-->
			</vac>
		</template>
	</AppNavigationItem>
	<AppNavigationItem v-else
		:title="project.name"
		:class="{ selectedproject: selected }"
		:allow-collapse="true"
		:open="selected"
		:force-menu="false"
		@click="onProjectClick">
		<template #icon>
			<FolderIcon v-if="selected"
				class="icon folder-icon-primary"
				:size="20" />
			<FolderOutlineIcon v-else
				class="icon folder-icon"
				:size="20" />
		</template>
		<template #counter>
			<Actions>
				<ActionButton v-if="!pageIsPublic"
					class="detailButton"
					@click="onShareClick">
					<template #icon>
						<ShareVariantIcon :size="20" />
					</template>
				</ActionButton>
			</Actions>
			<Actions>
				<ActionButton
					class="detailButton"
					@click="onDetailClick">
					<template #icon>
						<CogIcon :size="20" />
					</template>
				</ActionButton>
			</Actions>
		</template>
		<template #actions>
			<ActionButton v-if="maintenerAccess"
				@click="onAddMemberClick">
				<template #icon>
					<AccountIcon :size="20" />
				</template>
				{{ t('cospend', 'Add member') }}
			</ActionButton>
			<ActionButton
				:close-after-click="true"
				@click="onStatsClick">
				<template #icon>
					<ChartLineIcon
						class="icon"
						:size="20" />
				</template>
				{{ t('cospend', 'Statistics') }}
			</ActionButton>
			<ActionButton
				:close-after-click="true"
				@click="onSettleClick">
				<template #icon>
					<ReimburseIcon :size="20" />
				</template>
				{{ t('cospend', 'Project settlement') }}
			</ActionButton>
			<ActionButton v-if="adminAccess"
				:close-after-click="true"
				@click="onDeleteProjectClick">
				<template #icon>
					<DeleteIcon :size="20" />
				</template>
				{{ t('cospend', 'Delete') }}
			</ActionButton>
		</template>
		<template #default>
			<AppNavigationItem v-if="members.length < 1"
				:title="t('cospend', 'Add a member')"
				@click="onAddMemberClick">
				<template #icon>
					<PlusIcon :size="20" />
				</template>
			</AppNavigationItem>
			<AppNavigationMemberItem v-for="member in sortedMembers"
				:key="member.id"
				class="memberItem"
				:member="member"
				:selected="selectedMemberId === member.id"
				:project-id="project.id"
				:in-navigation="true"
				:precision="precision"
				@click="onMemberClick(member.id)"
				@member-edited="onMemberEdited" />
		</template>
	</AppNavigationItem>
</template>

<script>
import ShareVariantIcon from 'vue-material-design-icons/ShareVariant'
import CogIcon from 'vue-material-design-icons/Cog'
import PlusIcon from 'vue-material-design-icons/Plus'
import DeleteIcon from 'vue-material-design-icons/Delete'
import AccountIcon from 'vue-material-design-icons/Account'
import FolderIcon from 'vue-material-design-icons/Folder'
import FolderOutlineIcon from 'vue-material-design-icons/FolderOutline'
import ChartLineIcon from 'vue-material-design-icons/ChartLine'
import ClickOutside from 'vue-click-outside'
import AppNavigationMemberItem from './AppNavigationMemberItem'

import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'

import cospend from '../state'
import * as constants from '../constants'
import { Timer, getSortedMembers } from '../utils'
import ReimburseIcon from './ReimburseIcon'

export default {
	name: 'AppNavigationProjectItem',
	components: {
		ReimburseIcon,
		AppNavigationMemberItem,
		AppNavigationItem,
		ActionButton,
		Actions,
		ChartLineIcon,
		FolderIcon,
		FolderOutlineIcon,
		CogIcon,
		ShareVariantIcon,
		AccountIcon,
		PlusIcon,
		DeleteIcon,
	},
	directives: {
		ClickOutside,
	},
	props: {
		project: {
			type: Object,
			required: true,
		},
		members: {
			type: Array,
			required: true,
		},
		selected: {
			type: Boolean,
			required: true,
		},
		selectedMemberId: {
			type: Number,
			default: null,
		},
		memberOrder: {
			type: String,
			default: 'name',
		},
	},
	data() {
		return {
			deleting: false,
			deletionTimer: null,
		}
	},
	computed: {
		pageIsPublic() {
			return cospend.pageIsPublic
		},
		maintenerAccess() {
			return this.project.myaccesslevel >= constants.ACCESS.MAINTENER
		},
		adminAccess() {
			return this.project.myaccesslevel >= constants.ACCESS.ADMIN
		},
		precision() {
			// here we determine how much precision is necessary to display correct balances (#117)
			return this.project.precision
		},
		sortedMembers() {
			return getSortedMembers(this.members, this.memberOrder)
		},
	},
	beforeMount() {
	},
	methods: {
		onProjectClick(e) {
			this.$emit('project-clicked', this.project.id)
		},
		onMemberClick(memberId) {
			this.$emit('member-click', memberId)
		},
		onDeleteProjectClick() {
			this.deleting = true
			this.deletionTimer = new Timer(() => {
				this.$emit('delete-project', this.project.id)
			}, 7000)
		},
		cancelDeletion() {
			this.deleting = false
			this.deletionTimer.pause()
			delete this.deletionTimer
		},
		onStatsClick() {
			this.$emit('stats-clicked', this.project.id)
		},
		onSettleClick() {
			this.$emit('settle-clicked', this.project.id)
		},
		onDetailClick() {
			this.$emit('detail-clicked', this.project.id)
		},
		onShareClick() {
			this.$emit('share-clicked', this.project.id)
		},
		onAddMemberClick() {
			this.$emit('new-member-clicked', this.project.id)
		},
		onMemberEdited(projectid, memberid) {
			this.$emit('member-edited', projectid, memberid)
		},
	},
}
</script>

<style scoped lang="scss">
.memberItem {
	height: 44px;
	padding-left: 30px !important;
	padding-right: 0 !important;
}

::v-deep .detailButton {
	border-radius: 50%;
	&:hover {
		background-color: var(--color-background-darker);
	}
	button {
		padding-right: 0 !important;
		border-radius: 50%;
	}
}

.folder-icon-primary {
	color: var(--color-primary);
}
</style>
