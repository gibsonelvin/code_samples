import { apiV2 } from 'fetch-api'
import { onValueChange, useApiQuery } from 'hooks'

export default function useSelectedUser(userId) {
	const apiMethod = apiV2.user.get.byId
	const userQuery = useApiQuery(apiMethod, apiMethod.key, userId, { enabled: false })
	onValueChange(userId, (newUserId) => {
		if (newUserId[0]) {
			userQuery.refetch()
		}
	})
	return userQuery
}
