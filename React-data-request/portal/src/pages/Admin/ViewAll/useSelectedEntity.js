import { apiV2 } from 'fetch-api'
import { onValueChange, useApiQuery } from 'hooks'

export default function useSelectedEntity(entityId) {
	const apiMethod = apiV2.entity.get.byId
	const entityQuery = useApiQuery(apiMethod, apiMethod.key, entityId, { enabled: false })
	onValueChange(entityId, (newEntityId) => {
		if (newEntityId[0]) {
			entityQuery.refetch()
		}
	})
	return entityQuery
}
