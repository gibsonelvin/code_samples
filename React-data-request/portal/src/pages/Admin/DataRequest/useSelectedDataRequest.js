import { apiV2 } from 'fetch-api'
import { onValueChange, useApiQuery } from 'hooks'

export default function useSelectedDataRequest(dataRequest) {
	const apiMethod = apiV2.dataRequestConstituent.get.byDataRequestId
	const dataRequestQuery = useApiQuery(apiMethod, apiMethod.key, [dataRequest], { enabled: false })
	onValueChange(dataRequest, (newDataRequest) => {
		if (newDataRequest) {
			dataRequestQuery.refetch()
		}
	})
	return dataRequestQuery
}
